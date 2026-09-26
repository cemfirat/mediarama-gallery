<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

final readonly class ImageMagickResourceLimits
{
    public function __construct(
        private string $memory = '256MiB',
        private string $map = '512MiB',
        private string $disk = '2GiB',
        private string $area = '512MiB',
        private int $width = 32768,
        private int $height = 32768,
        private int $files = 128,
        private int $threads = 2,
        private int $timeSeconds = 45,
        private int $listLength = 32,
    ) {
        foreach ([
            'memory' => $this->memory,
            'map' => $this->map,
            'disk' => $this->disk,
        ] as $name => $value) {
            if (!preg_match('/^\d+(?:\.\d+)?(?:B|[KMGTPE]i?B)?$/i', $value)) {
                throw new \InvalidArgumentException(sprintf(
                    'ImageMagick %s limit must be a finite byte value with an optional SI/IEC suffix.',
                    $name,
                ));
            }
        }

        if (!preg_match('/^\d+(?:\.\d+)?(?:B|[KMGTPE]i?B|[KMGTPE]?P)?$/i', $this->area)) {
            throw new \InvalidArgumentException(
                'ImageMagick area limit must be a finite byte or pixel-cache area value.',
            );
        }

        foreach ([
            'width' => $this->width,
            'height' => $this->height,
            'files' => $this->files,
            'threads' => $this->threads,
            'time' => $this->timeSeconds,
            'list length' => $this->listLength,
        ] as $name => $value) {
            if ($value < 1) {
                throw new \InvalidArgumentException(sprintf(
                    'ImageMagick %s limit must be positive.',
                    $name,
                ));
            }
        }
    }

    /** @return list<string> */
    public function commandArguments(): array
    {
        return [
            '-limit', 'memory', $this->memory,
            '-limit', 'map', $this->map,
            '-limit', 'disk', $this->disk,
            '-limit', 'area', $this->area,
            '-limit', 'width', (string) $this->width,
            '-limit', 'height', (string) $this->height,
            '-limit', 'file', (string) $this->files,
            '-limit', 'thread', (string) $this->threads,
            '-limit', 'time', (string) $this->timeSeconds,
        ];
    }

    /** @return array<string, string> */
    public function environment(): array
    {
        return [
            'MAGICK_LIST_LENGTH_LIMIT' => (string) $this->listLength,
        ];
    }
}
