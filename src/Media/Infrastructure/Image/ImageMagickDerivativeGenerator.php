<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use DateTimeImmutable;
use Mediarama\Media\Application\ImageDerivativeGenerator;
use Mediarama\Media\Application\ImageDerivativeProfile;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaDerivative;
use Mediarama\Media\Domain\StorageObjectId;
use Symfony\Component\Uid\Uuid;

final readonly class ImageMagickDerivativeGenerator implements ImageDerivativeGenerator
{
    public function __construct(
        private MediaStorage $storage,
        private ImageMagickProcess $process,
    ) {
    }

    public function generate(MediaAsset $media, ImageDerivativeProfile $profile, int $processingVersion): MediaDerivative
    {
        $source = $this->storage->read($media->original);
        $input = tempnam(sys_get_temp_dir(), 'mediarama-image-in-');
        $output = tempnam(sys_get_temp_dir(), 'mediarama-image-out-');

        if ($input === false || $output === false) {
            throw new \RuntimeException('Unable to allocate image processing files.');
        }

        $outputWithExtension = $output.'.'.$profile->format;

        try {
            $inputHandle = fopen($input, 'wb');
            stream_copy_to_stream($source, $inputHandle);
            fclose($inputHandle);
            fclose($source);

            $arguments = [
                $input.'[0]',
                '-auto-orient',
                '-strip',
                '-thumbnail', sprintf('%dx%d>', $profile->maximumWidth, $profile->maximumHeight),
                '-quality', (string) $profile->quality,
                $outputWithExtension,
            ];

            $this->process->run($arguments);

            $imageInfo = getimagesize($outputWithExtension);
            if ($imageInfo === false) {
                throw new \RuntimeException('Generated derivative is not a readable image.');
            }

            $storageId = new StorageObjectId(
                'media',
                sprintf(
                    'derivatives/%s/v%d/%s.%s',
                    $media->id->toRfc4122(),
                    $processingVersion,
                    $profile->name,
                    $profile->format,
                ),
            );

            $stream = fopen($outputWithExtension, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Unable to read generated derivative.');
            }

            $stored = $this->storage->write($storageId, $stream, $imageInfo['mime'] ?? null);
            fclose($stream);

            $now = new DateTimeImmutable();

            return new MediaDerivative(
                Uuid::v7(),
                $media->id,
                'image',
                $profile->name,
                $processingVersion,
                $storageId,
                (string) ($imageInfo['mime'] ?? 'application/octet-stream'),
                $stored->byteSize,
                (int) $imageInfo[0],
                (int) $imageInfo[1],
                null,
                [
                    'orientation_normalized' => true,
                    'watermarked' => $profile->watermark,
                ],
                $now,
                $now,
            );
        } finally {
            @unlink($input);
            @unlink($output);
            @unlink($outputWithExtension);
        }
    }
}
