<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class MediaDerivative
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public Uuid $id,
        public Uuid $mediaId,
        public string $kind,
        public string $profile,
        public int $processingVersion,
        public StorageObjectId $storage,
        public string $mimeType,
        public int $byteSize,
        public ?int $width,
        public ?int $height,
        public ?int $durationMs,
        public array $metadata,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($processingVersion < 1 || $byteSize < 0) {
            throw new \InvalidArgumentException('Invalid derivative version or byte size.');
        }
    }
}
