<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\StorageObjectId;

final readonly class StoredObject
{
    public function __construct(
        public StorageObjectId $id,
        public int $byteSize,
        public ?string $checksum = null,
        public ?string $contentType = null,
        public ?string $etag = null,
    ) {
        if ($byteSize < 0) {
            throw new \InvalidArgumentException('Stored object byte size must not be negative.');
        }
    }
}
