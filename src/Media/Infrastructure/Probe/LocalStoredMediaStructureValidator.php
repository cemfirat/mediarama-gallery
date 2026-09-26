<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Probe;

use Mediarama\Media\Application\InspectImageFileGeometry;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ValidateStoredMediaStructure;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;

final readonly class LocalStoredMediaStructureValidator implements ValidateStoredMediaStructure
{
    public function __construct(
        private MediaStorage $storage,
        private InspectImageFileGeometry $imageGeometry,
        private FfprobeProcess $ffprobe,
    ) {
    }

    public function __invoke(StorageObjectId $object, MediaType $mediaType): void
    {
        $source = $this->storage->read($object);
        $path = tempnam(sys_get_temp_dir(), 'mediarama-structure-');

        if ($path === false) {
            fclose($source);
            throw new \RuntimeException('Unable to allocate media structure inspection file.');
        }

        try {
            $target = fopen($path, 'wb');
            if ($target === false) {
                fclose($source);
                throw new \RuntimeException('Unable to create media structure inspection file.');
            }

            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new \RuntimeException('Unable to copy media for structural validation.');
                }
            } finally {
                fclose($target);
                fclose($source);
            }

            match ($mediaType) {
                MediaType::Image => $this->validateImage($path),
                MediaType::Audio, MediaType::Video => $this->validateAv($path, $mediaType),
                MediaType::Document => throw new \DomainException('Generic document uploads are not enabled.'),
            };
        } finally {
            @unlink($path);
        }
    }

    private function validateImage(string $path): void
    {
        try {
            ($this->imageGeometry)($path);
        } catch (\RuntimeException $error) {
            throw new \DomainException('Uploaded image failed structural validation.', 0, $error);
        }
    }

    private function validateAv(string $path, MediaType $mediaType): void
    {
        try {
            $streamTypes = $this->ffprobe->streamTypes($path);
        } catch (\RuntimeException $error) {
            throw new \DomainException(
                sprintf('Uploaded %s failed structural validation.', $mediaType->value),
                0,
                $error,
            );
        }

        if (!in_array($mediaType->value, $streamTypes, true)) {
            throw new \DomainException(sprintf(
                'Uploaded %s does not contain a %s stream.',
                $mediaType->value,
                $mediaType->value,
            ));
        }
    }
}
