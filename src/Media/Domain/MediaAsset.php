<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class MediaAsset
{
    /** @param array<string, mixed> $metadata */
    private function __construct(
        public readonly Uuid $id,
        public readonly ?Uuid $ownerId,
        public readonly StorageObjectId $original,
        public readonly string $originalFilename,
        public readonly string $mimeType,
        public readonly MediaType $mediaType,
        public readonly int $byteSize,
        public readonly string $checksumSha256,
        public ProcessingState $processingState,
        public ModerationState $moderationState,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $durationMs = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?DateTimeImmutable $capturedAt = null,
        public array $metadata = [],
    ) {
        if ($byteSize < 0) {
            throw new \InvalidArgumentException('Media byte size must not be negative.');
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $checksumSha256)) {
            throw new \InvalidArgumentException('Media checksum must be a lowercase SHA-256 hex string.');
        }
    }

    /** @param array<string, mixed> $metadata */
    public static function createWithId(
        Uuid $id,
        ?Uuid $ownerId,
        StorageObjectId $original,
        string $originalFilename,
        string $mimeType,
        MediaType $mediaType,
        int $byteSize,
        string $checksumSha256,
        array $metadata = [],
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            $id,
            $ownerId,
            $original,
            $originalFilename,
            $mimeType,
            $mediaType,
            $byteSize,
            $checksumSha256,
            ProcessingState::Processing,
            ModerationState::Draft,
            $now,
            $now,
            metadata: $metadata,
        );
    }

    /** @param array<string, mixed> $metadata */
    public static function create(
        ?Uuid $ownerId,
        StorageObjectId $original,
        string $originalFilename,
        string $mimeType,
        MediaType $mediaType,
        int $byteSize,
        string $checksumSha256,
        array $metadata = [],
    ): self {
        return self::createWithId(
            Uuid::v7(),
            $ownerId,
            $original,
            $originalFilename,
            $mimeType,
            $mediaType,
            $byteSize,
            $checksumSha256,
            $metadata,
        );
    }

    public function markReady(): void
    {
        $this->processingState = ProcessingState::Ready;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->processingState = ProcessingState::Failed;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function submitForReview(): void
    {
        if ($this->processingState !== ProcessingState::Ready) {
            throw new \DomainException('Only ready media can be submitted for review.');
        }

        $this->moderationState = ModerationState::PendingReview;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function publish(): void
    {
        if ($this->processingState !== ProcessingState::Ready) {
            throw new \DomainException('Only ready media can be published.');
        }

        $this->moderationState = ModerationState::Published;
        $this->updatedAt = new DateTimeImmutable();
    }
}
