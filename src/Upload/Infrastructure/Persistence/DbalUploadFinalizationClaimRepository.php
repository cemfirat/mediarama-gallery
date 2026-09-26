<?php

declare(strict_types=1);

namespace Mediarama\Upload\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Mediarama\Upload\Application\UploadFinalizationClaimRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DbalUploadFinalizationClaimRepository implements UploadFinalizationClaimRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findReservedMediaId(Uuid $sessionId): ?Uuid
    {
        $value = $this->connection->fetchOne(
            'SELECT finalization_media_id FROM upload_sessions WHERE id = :id',
            ['id' => $sessionId->toRfc4122()],
        );

        return $value === false || $value === null
            ? null
            : Uuid::fromString((string) $value);
    }

    public function claim(Uuid $sessionId, Uuid $candidateMediaId): Uuid
    {
        $value = $this->connection->fetchOne(
            <<<'SQL'
UPDATE upload_sessions
SET finalization_media_id = COALESCE(finalization_media_id, :candidate),
    status = 'finalizing',
    updated_at = NOW()
WHERE id = :id
  AND status IN ('uploaded', 'finalizing')
RETURNING finalization_media_id
SQL,
            [
                'id' => $sessionId->toRfc4122(),
                'candidate' => $candidateMediaId->toRfc4122(),
            ],
        );

        if ($value !== false && $value !== null) {
            return Uuid::fromString((string) $value);
        }

        $row = $this->connection->fetchAssociative(
            'SELECT status, finalization_media_id FROM upload_sessions WHERE id = :id',
            ['id' => $sessionId->toRfc4122()],
        );

        if ($row === false) {
            throw new \DomainException('Upload session not found.');
        }

        if ((string) $row['status'] === 'completed' && $row['finalization_media_id'] !== null) {
            return Uuid::fromString((string) $row['finalization_media_id']);
        }

        throw new \DomainException('Upload session cannot be claimed for finalization from its current state.');
    }
}
