<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportCheckpointRepository;
use Mediarama\Import\Application\ImportMappingRepository;
use Symfony\Component\Uid\Uuid;

final readonly class CoppermineCollectionImporter
{
    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private Connection $target,
        private ImportMappingRepository $mappings,
        private ImportCheckpointRepository $checkpoints,
        private CoppermineTablePrefix $prefix,
    ) {
    }

    public function importCategories(int $batchSize = 100): int
    {
        $source = $this->sourceFactory->create();
        $cursor = (int) ($this->checkpoints->get('coppermine', 'categories') ?? '0');
        $table = $source->quoteIdentifier($this->prefix->table('categories'));

        $rows = $source->fetchAllAssociative(
            sprintf(
                'SELECT cid, owner_id, name, description, pos, parent FROM %s WHERE cid > :cursor ORDER BY cid ASC LIMIT %d',
                $table,
                max(1, min($batchSize, 1000)),
            ),
            ['cursor' => $cursor],
        );

        foreach ($rows as $row) {
            $sourceId = (string) $row['cid'];
            $targetId = $this->mappings->findTargetId('coppermine', 'category', $sourceId) ?? Uuid::v7();

            $ownerId = null;
            if ((int) $row['owner_id'] > 0) {
                $ownerId = $this->mappings->findTargetId('coppermine', 'user', (string) $row['owner_id']);
            }

            $this->target->executeStatement(
                <<<'SQL'
INSERT INTO collections (
    id, owner_id, parent_id, slug, title, description, visibility, position,
    created_at, updated_at
) VALUES (
    :id, :owner_id, NULL, NULL, :title, :description, 'public', :position,
    NOW(), NOW()
)
ON CONFLICT (id) DO UPDATE SET
    owner_id = EXCLUDED.owner_id,
    title = EXCLUDED.title,
    description = EXCLUDED.description,
    position = EXCLUDED.position,
    updated_at = NOW()
SQL,
                [
                    'id' => $targetId->toRfc4122(),
                    'owner_id' => $ownerId?->toRfc4122(),
                    'title' => (string) $row['name'],
                    'description' => (string) $row['description'],
                    'position' => (int) $row['pos'],
                ],
            );

            $this->mappings->remember('coppermine', 'category', $sourceId, $targetId);
            $this->checkpoints->save('coppermine', 'categories', $sourceId);
        }

        if (count($rows) < $batchSize) {
            $this->resolveCategoryParents($source);
        }

        return count($rows);
    }

    public function importAlbums(int $batchSize = 100): int
    {
        $source = $this->sourceFactory->create();
        $cursor = (int) ($this->checkpoints->get('coppermine', 'albums') ?? '0');
        $table = $source->quoteIdentifier($this->prefix->table('albums'));

        $rows = $source->fetchAllAssociative(
            sprintf(
                'SELECT aid, title, description, visibility, pos, category, owner FROM %s WHERE aid > :cursor ORDER BY aid ASC LIMIT %d',
                $table,
                max(1, min($batchSize, 1000)),
            ),
            ['cursor' => $cursor],
        );

        foreach ($rows as $row) {
            $sourceId = (string) $row['aid'];
            $targetId = $this->mappings->findTargetId('coppermine', 'album', $sourceId) ?? Uuid::v7();
            $ownerId = $this->mappings->findTargetId('coppermine', 'user', (string) $row['owner']);
            $parentId = (int) $row['category'] > 0
                ? $this->mappings->findTargetId('coppermine', 'category', (string) $row['category'])
                : null;

            // Coppermine visibility=0 means public. Other values encode group/user
            // restrictions and are imported conservatively until ACL conversion.
            $visibility = (int) $row['visibility'] === 0 ? 'public' : 'restricted';

            $this->target->executeStatement(
                <<<'SQL'
INSERT INTO collections (
    id, owner_id, parent_id, slug, title, description, visibility, position,
    created_at, updated_at
) VALUES (
    :id, :owner_id, :parent_id, NULL, :title, :description, :visibility, :position,
    NOW(), NOW()
)
ON CONFLICT (id) DO UPDATE SET
    owner_id = EXCLUDED.owner_id,
    parent_id = EXCLUDED.parent_id,
    title = EXCLUDED.title,
    description = EXCLUDED.description,
    visibility = EXCLUDED.visibility,
    position = EXCLUDED.position,
    updated_at = NOW()
SQL,
                [
                    'id' => $targetId->toRfc4122(),
                    'owner_id' => $ownerId?->toRfc4122(),
                    'parent_id' => $parentId?->toRfc4122(),
                    'title' => (string) $row['title'],
                    'description' => (string) $row['description'],
                    'visibility' => $visibility,
                    'position' => (int) $row['pos'],
                ],
            );

            $this->mappings->remember('coppermine', 'album', $sourceId, $targetId);
            $this->checkpoints->save('coppermine', 'albums', $sourceId);
        }

        return count($rows);
    }

    private function resolveCategoryParents(Connection $source): void
    {
        $table = $source->quoteIdentifier($this->prefix->table('categories'));
        $rows = $source->fetchAllAssociative(sprintf(
            'SELECT cid, parent FROM %s WHERE parent > 0 ORDER BY cid ASC',
            $table,
        ));

        foreach ($rows as $row) {
            $child = $this->mappings->findTargetId('coppermine', 'category', (string) $row['cid']);
            $parent = $this->mappings->findTargetId('coppermine', 'category', (string) $row['parent']);

            if ($child === null || $parent === null) {
                continue;
            }

            $this->target->update(
                'collections',
                ['parent_id' => $parent->toRfc4122(), 'updated_at' => (new \DateTimeImmutable())->format(DATE_ATOM)],
                ['id' => $child->toRfc4122()],
            );
        }
    }
}
