<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

final readonly class ImageDerivativeProfile
{
    public function __construct(
        public string $name,
        public int $maximumWidth,
        public int $maximumHeight,
        public string $format = 'webp',
        public int $quality = 82,
        public bool $watermark = false,
    ) {
        if ($maximumWidth < 1 || $maximumHeight < 1 || $quality < 1 || $quality > 100) {
            throw new \InvalidArgumentException('Invalid image derivative profile.');
        }
    }
}
