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
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO upload_finalizations (upload_session_id, media_id, created_at)
VALUES (:session, :media, NOW())
ON CONFLICT (upload_session_id) DO NOTHING
SQL,
            [
                'session' => $sessionId->toRfc4122(),
                'media' => $mediaId->toRfc4122(),
            ],
        );
    }
}
