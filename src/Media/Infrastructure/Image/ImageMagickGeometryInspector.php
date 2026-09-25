<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use Mediarama\Media\Application\InspectImageGeometry;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\MediaAsset;

final readonly class ImageMagickGeometryInspector implements InspectImageGeometry
{
    public function __construct(
        private MediaStorage $storage,
        private string $identifyBinary = 'identify',
    ) {
    }

    public function __invoke(MediaAsset $media): array
    {
        $source = $this->storage->read($media->original);
        $input = tempnam(sys_get_temp_dir(), 'mediarama-identify-');

        if ($input === false) {
            throw new \RuntimeException('Unable to allocate image inspection file.');
        }

        try {
            $out = fopen($input, 'wb');
            if ($out === false) {
                throw new \RuntimeException('Unable to create image inspection file.');
            }
            stream_copy_to_stream($source, $out);
            fclose($out);
            fclose($source);

            $process = new \Symfony\Component\Process\Process([
                $this->identifyBinary,
                '-format',
                '%w %h %[orientation]',
                $input.'[0]',
            ]);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Unable to inspect image geometry: '.trim($process->getErrorOutput()));
            }

            $parts = preg_split('/\s+/', trim($process->getOutput()));
            if ($parts === false || count($parts) < 2) {
                throw new \RuntimeException('Image geometry response is invalid.');
            }

            $width = (int) $parts[0];
            $height = (int) $parts[1];
            $orientation = strtolower((string) ($parts[2] ?? ''));

            if (in_array($orientation, ['lefttop', 'righttop', 'rightbottom', 'leftbottom'], true)) {
                [$width, $height] = [$height, $width];
            }

            if ($width < 1 || $height < 1) {
                throw new \RuntimeException('Image dimensions are invalid.');
            }

            return ['width' => $width, 'height' => $height];
        } finally {
            @unlink($input);
        }
    }
}
