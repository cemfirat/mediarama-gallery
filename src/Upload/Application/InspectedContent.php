<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Domain\MediaType;

final readonly class InspectedContent
{
    public function __construct(
        public string $mimeType,
        public MediaType $mediaType,
        public string $sha256,
        public int $byteSize,
    ) {
    }
}
