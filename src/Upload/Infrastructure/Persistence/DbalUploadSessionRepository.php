<?php

declare(strict_types=1);

namespace Mediarama\Upload\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Mediarama\Upload\Application\UploadSessionRepository;
use Mediarama\Upload\Domain\UploadSession;
use Mediarama\Upload\Domain\UploadStatus;
use Symfony\Component\Uid\Uuid;

final readonly class DbalUploadSessionRepository implements UploadSessionRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(UploadSession $session): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO upload_sessions (
    id, user_id, target_collection_id, original_filename, expected_size,
    expected_mime, temporary_storage_key, status, expires_at, created_at, updated_at
) VALUES (
    :id, :user_id, :target_collection_id, :original_filename, :expected_size,
    :expected_mime, :temporary_storage_key, :status, :expires_at, :created_at, :updated_at
)
ON CONFLICT (id) DO UPDATE SET
    target_collection_id = EXCLUDED.target_collection_id,
    expected_mime = EXCLUDED.expected_mime,
    status = EXCLUDED.status,
    expires_at = EXCLUDED.expires_at,
    updated_at = EXCLUDED.updated_at
SQL,
            [
                'id' => $session->id->toRfc4122(),
                'user_id' => $session->userId->toRfc4122(),
                'target_collection_id' => $session->targetCollectionId?->toRfc4122(),
                'original_filename' => $session->originalFilename,
                'expected_size' => $session->expectedSize,
                'expected_mime' => $session->expectedMime,
                'temporary_storage_key' => $session->temporaryStorageKey,
                'status' => $session->status->value,
                'expires_at' => $session->expiresAt->format(DATE_ATOM),
                'created_at' => $session->createdAt->format(DATE_ATOM),
                'updated_at' => $session->updatedAt->format(DATE_ATOM),
            ],
        );
    }

    public function get(Uuid $id): UploadSession
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM upload_sessions WHERE id = :id',
            ['id' => $id->toRfc4122()],
        );

        if ($row === false) {
            throw new \DomainException('Upload session not found.');
        }

        return UploadSession::reconstitute(
            Uuid::fromString((string) $row['id']),
            Uuid::fromString((string) $row['user_id']),
            $row['target_collection_id'] !== null ? Uuid::fromString((string) $row['target_collection_id']) : null,
            (string) $row['original_filename'],
            (int) $row['expected_size'],
            $row['expected_mime'] !== null ? (string) $row['expected_mime'] : null,
            (string) $row['temporary_storage_key'],
            UploadStatus::from((string) $row['status']),
            new DateTimeImmutable((string) $row['expires_at']),
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
