<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

final readonly class StorageObjectId
{
    public function __construct(
        public string $disk,
        public string $key,
    ) {
        if ($disk === '') {
            throw new \InvalidArgumentException('Storage disk must not be empty.');
        }

        if ($key === '' || str_starts_with($key, '/') || str_contains($key, '..')) {
            throw new \InvalidArgumentException('Invalid storage key.');
        }
    }

    public function __toString(): string
    {
        return $this->disk.':'.$this->key;
    }
}
