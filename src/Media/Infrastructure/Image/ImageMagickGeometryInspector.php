<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use Mediarama\Media\Application\InspectImageFileGeometry;
use Mediarama\Media\Application\InspectImageGeometry;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\MediaAsset;

final readonly class ImageMagickGeometryInspector implements InspectImageGeometry
{
    public function __construct(
        private MediaStorage $storage,
        private InspectImageFileGeometry $fileGeometry,
    ) {
    }

    public function __invoke(MediaAsset $media): array
    {
        $source = $this->storage->read($media->original);
        $input = tempnam(sys_get_temp_dir(), 'mediarama-identify-');

        if ($input === false) {
            fclose($source);
            throw new \RuntimeException('Unable to allocate image inspection file.');
        }

        try {
            $out = fopen($input, 'wb');
            if ($out === false) {
                fclose($source);
                throw new \RuntimeException('Unable to create image inspection file.');
            }

            try {
                if (stream_copy_to_stream($source, $out) === false) {
                    throw new \RuntimeException('Unable to copy image for geometry inspection.');
                }
            } finally {
                fclose($out);
                fclose($source);
            }

            return ($this->fileGeometry)($input);
        } finally {
            @unlink($input);
        }
    }
}
