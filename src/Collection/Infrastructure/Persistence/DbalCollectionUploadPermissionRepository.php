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
            [
                'collection' => $collectionId->toRfc4122(),
                'user' => $userId->toRfc4122(),
            ],
        );

        if ($owner !== false) {
            return true;
        }

        $allowed = $this->connection->fetchOne(
            <<<'SQL'
SELECT 1
FROM user_groups ug
JOIN group_permissions gp ON gp.group_id = ug.group_id
WHERE ug.user_id = :user
  AND gp.permission_key = 'collection.media.add'
LIMIT 1
SQL,
            ['user' => $userId->toRfc4122()],
        );

        return $allowed !== false;
    }
}
