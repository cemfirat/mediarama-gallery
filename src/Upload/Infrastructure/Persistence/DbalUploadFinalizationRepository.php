<?php

declare(strict_types=1);

namespace Mediarama\Upload\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Mediarama\Upload\Application\UploadFinalizationRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DbalUploadFinalizationRepository implements UploadFinalizationRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findMediaId(Uuid $sessionId): ?Uuid
    {
        $value = $this->connection->fetchOne(
            'SELECT media_id FROM upload_finalizations WHERE upload_session_id = :id',
            ['id' => $sessionId->toRfc4122()],
        );

        return $value === false ? null : Uuid::fromString((string) $value);
    }

    public function remember(Uuid $sessionId, Uuid $mediaId): void
    {
        $value = $this->connection->fetchOne(
            <<<'SQL'
INSERT INTO upload_finalizations (upload_session_id, media_id, created_at)
VALUES (:session, :media, NOW())
ON CONFLICT (upload_session_id)
DO UPDATE SET upload_session_id = EXCLUDED.upload_session_id
RETURNING media_id
SQL,
            [
                'session' => $sessionId->toRfc4122(),
                'media' => $mediaId->toRfc4122(),
            ],
        );

        if ($value === false || !Uuid::fromString((string) $value)->equals($mediaId)) {
            throw new \LogicException('Upload finalization mapping conflicts with the reserved MediaAsset identity.');
        }
    }

    public function isProcessingDispatched(Uuid $sessionId): bool
    {
        $row = $this->connection->fetchAssociative(
            'SELECT processing_dispatched_at FROM upload_finalizations WHERE upload_session_id = :id',
            ['id' => $sessionId->toRfc4122()],
        );

        if ($row === false) {
            throw new \LogicException('Upload finalization mapping is missing.');
        }

        return $row['processing_dispatched_at'] !== null;
    }

    public function markProcessingDispatched(Uuid $sessionId): void
    {
        $updated = $this->connection->executeStatement(
            <<<'SQL'
UPDATE upload_finalizations
SET processing_dispatched_at = COALESCE(processing_dispatched_at, NOW())
WHERE upload_session_id = :id
SQL,
            ['id' => $sessionId->toRfc4122()],
        );

        if ($updated !== 1) {
            throw new \LogicException('Unable to mark upload processing dispatch.');
        }
    }
}
