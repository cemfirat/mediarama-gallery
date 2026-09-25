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
FROM collection_access ca
WHERE ca.collection_id = :collection
  AND ca.capability = 'collection.media.add'
  AND ca.effect = 'allow'
  AND (
      ca.user_id = :user
      OR ca.group_id IN (
          SELECT ug.group_id
          FROM user_groups ug
          WHERE ug.user_id = :user
      )
  )
LIMIT 1
SQL,
            [
                'collection' => $collectionId->toRfc4122(),
                'user' => $userId->toRfc4122(),
            ],
        );

        return $allowed !== false;
    }
}
