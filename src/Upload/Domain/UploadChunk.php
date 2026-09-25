<?php

declare(strict_types=1);

namespace Mediarama\Upload\Domain;

final readonly class UploadChunk
{
    public function __construct(
        public int $index,
        public int $offset,
        public int $size,
        public string $checksumSha256,
    ) {
        if ($index < 0 || $offset < 0 || $size <= 0) {
            throw new \InvalidArgumentException('Invalid upload chunk coordinates.');
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $checksumSha256)) {
            throw new \InvalidArgumentException('Chunk checksum must be a lowercase SHA-256 hex string.');
        }
    }
}
