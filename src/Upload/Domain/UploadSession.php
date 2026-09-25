<?php

declare(strict_types=1);

namespace Mediarama\Upload\Domain;

use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class UploadSession
{
    private function __construct(
        public readonly Uuid $id,
        public readonly Uuid $userId,
        public readonly ?Uuid $targetCollectionId,
        public readonly string $originalFilename,
        public readonly int $expectedSize,
        public readonly ?string $expectedMime,
        public readonly string $temporaryStorageKey,
        public UploadStatus $status,
        public readonly DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($expectedSize < 0) {
            throw new \InvalidArgumentException('Expected upload size must not be negative.');
        }
        if (trim($originalFilename) === '') {
            throw new \InvalidArgumentException('Original filename must not be empty.');
        }
    }

    public static function create(
        Uuid $userId,
        ?Uuid $targetCollectionId,
        string $originalFilename,
        int $expectedSize,
        ?string $expectedMime = null,
    ): self {
        $id = Uuid::v7();
        $now = new DateTimeImmutable();

        return new self(
            $id,
            $userId,
            $targetCollectionId,
            trim($originalFilename),
            $expectedSize,
            $expectedMime,
            sprintf('temporary/%s/source', $id->toRfc4122()),
            UploadStatus::Created,
            $now->add(new DateInterval('PT24H')),
            $now,
            $now,
        );
    }

    public function begin(): void
    {
        if ($this->status !== UploadStatus::Created) {
            throw new \DomainException('Only a new upload session can begin uploading.');
        }
        $this->status = UploadStatus::Uploading;
        $this->touch();
    }

    public function markUploaded(): void
    {
        if (!in_array($this->status, [UploadStatus::Created, UploadStatus::Uploading], true)) {
            throw new \DomainException('Upload session cannot be marked uploaded from its current state.');
        }
        $this->status = UploadStatus::Uploaded;
        $this->touch();
    }

    public function beginFinalization(): void
    {
        if ($this->status !== UploadStatus::Uploaded) {
            throw new \DomainException('Only an uploaded session can be finalized.');
        }
        $this->status = UploadStatus::Finalizing;
        $this->touch();
    }

    public function complete(): void
    {
        if ($this->status !== UploadStatus::Finalizing) {
            throw new \DomainException('Only a finalizing upload can complete.');
        }
        $this->status = UploadStatus::Completed;
        $this->touch();
    }

    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $now >= $this->expiresAt && $this->status !== UploadStatus::Completed;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
