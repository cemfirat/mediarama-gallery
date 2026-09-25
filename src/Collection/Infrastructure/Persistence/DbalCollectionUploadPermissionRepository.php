<?php

declare(strict_types=1);

namespace Mediarama\Collection\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Mediarama\Collection\Application\CollectionUploadPermissionRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DbalCollectionUploadPermissionRepository implements CollectionUploadPermissionRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function userCanUpload(Uuid $userId, Uuid $collectionId): bool
    {
        $owner = $this->connection->fetchOne(
            'SELECT 1 FROM collections WHERE id = :collection AND owner_id = :user AND deleted_at IS NULL',
            ['collection' => $collectionId->toRfc4122(), 'user' => $userId->toRfc4122()],
        );

        if ($owner !== false) {
            return true;
        }

        $allowed = $this->connection->fetchOne(
            <<<'SQL'
SELECT 1
FROM group_members gm
JOIN group_permissions gp ON gp.group_id = gm.group_id
JOIN permissions p ON p.id = gp.permission_id
WHERE gm.user_id = :user
  AND p.code = 'collection.media.add'
LIMIT 1
SQL,
            ['user' => $userId->toRfc4122()],
        );

        return $allowed !== false;
    }
}
