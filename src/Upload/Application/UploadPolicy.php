<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

final readonly class UploadPolicy
{
    public function __construct(
        public int $maximumAssetBytes,
        public int $maximumChunkBytes,
    ) {
        if ($maximumAssetBytes <= 0 || $maximumChunkBytes <= 0) {
            throw new \InvalidArgumentException('Upload size limits must be positive.');
        }

        if ($maximumChunkBytes > $maximumAssetBytes) {
            throw new \InvalidArgumentException('Chunk limit cannot exceed asset limit.');
        }
    }

    public function assertAssetSize(int $bytes): void
    {
        if ($bytes < 0 || $bytes > $this->maximumAssetBytes) {
            throw new \DomainException('Asset exceeds the configured upload size limit.');
        }
    }

    public function assertChunkSize(int $bytes): void
    {
        if ($bytes <= 0 || $bytes > $this->maximumChunkBytes) {
            throw new \DomainException('Chunk exceeds the configured upload chunk size limit.');
        }
    }
}
