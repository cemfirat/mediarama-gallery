<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Mediarama\Media\Application\MediaDerivativeRepository;
use Mediarama\Media\Domain\MediaDerivative;
use Mediarama\Media\Domain\StorageObjectId;
use Symfony\Component\Uid\Uuid;

final readonly class DbalMediaDerivativeRepository implements MediaDerivativeRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(MediaDerivative $d): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO media_derivatives (
 id, media_id, kind, profile, processing_version, storage_disk, storage_key,
 mime_type, byte_size, width, height, duration_ms, metadata, created_at, updated_at
) VALUES (
 :id, :media_id, :kind, :profile, :version, :disk, :key,
 :mime, :bytes, :width, :height, :duration, CAST(:metadata AS JSONB), :created, :updated
)
ON CONFLICT (media_id, kind, profile, processing_version) DO UPDATE SET
 storage_disk = EXCLUDED.storage_disk,
 storage_key = EXCLUDED.storage_key,
 mime_type = EXCLUDED.mime_type,
 byte_size = EXCLUDED.byte_size,
 width = EXCLUDED.width,
 height = EXCLUDED.height,
 duration_ms = EXCLUDED.duration_ms,
 metadata = EXCLUDED.metadata,
 updated_at = EXCLUDED.updated_at
SQL,
            [
                'id' => $d->id->toRfc4122(),
                'media_id' => $d->mediaId->toRfc4122(),
                'kind' => $d->kind,
                'profile' => $d->profile,
                'version' => $d->processingVersion,
                'disk' => $d->storage->disk,
                'key' => $d->storage->key,
                'mime' => $d->mimeType,
                'bytes' => $d->byteSize,
                'width' => $d->width,
                'height' => $d->height,
                'duration' => $d->durationMs,
                'metadata' => json_encode($d->metadata, JSON_THROW_ON_ERROR),
                'created' => $d->createdAt->format(DATE_ATOM),
                'updated' => $d->updatedAt->format(DATE_ATOM),
            ],
        );
    }

    public function find(Uuid $mediaId, string $kind, string $profile, int $processingVersion): ?MediaDerivative
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
SELECT * FROM media_derivatives
WHERE media_id = :media AND kind = :kind AND profile = :profile AND processing_version = :version
SQL,
            [
                'media' => $mediaId->toRfc4122(),
                'kind' => $kind,
                'profile' => $profile,
                'version' => $processingVersion,
            ],
        );

        if ($row === false) {
            return null;
        }

        return new MediaDerivative(
            Uuid::fromString((string) $row['id']),
            Uuid::fromString((string) $row['media_id']),
            (string) $row['kind'],
            (string) $row['profile'],
            (int) $row['processing_version'],
            new StorageObjectId((string) $row['storage_disk'], (string) $row['storage_key']),
            (string) $row['mime_type'],
            (int) $row['byte_size'],
            $row['width'] !== null ? (int) $row['width'] : null,
            $row['height'] !== null ? (int) $row['height'] : null,
            $row['duration_ms'] !== null ? (int) $row['duration_ms'] : null,
            json_decode((string) $row['metadata'], true, flags: JSON_THROW_ON_ERROR),
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
