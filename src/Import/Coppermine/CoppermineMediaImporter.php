<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportCheckpointRepository;
use Mediarama\Import\Application\ImportMappingRepository;
use Mediarama\Media\Application\ContentInspector;
use Mediarama\Media\Application\MediaStorage;
use Symfony\Component\Uid\Uuid;

final readonly class CoppermineMediaImporter
{
    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private Connection $target,
        private ImportMappingRepository $mappings,
        private ImportCheckpointRepository $checkpoints,
        private CoppermineTablePrefix $prefix,
        private CoppermineFileLocator $files,
        private MediaStorage $storage,
        private ContentInspector $inspector,
    ) {
    }

    public function importBatch(int $batchSize = 50): CoppermineMediaImportReport
    {
        $source = $this->sourceFactory->create();
        $cursor = (int) ($this->checkpoints->get('coppermine', 'pictures') ?? '0');
        $table = $source->quoteIdentifier($this->prefix->table('pictures'));

        $rows = $source->fetchAllAssociative(
            sprintf(
                'SELECT pid, aid, filepath, filename, filesize, pwidth, pheight, ctime, owner_id, title, caption, keywords, approved, position FROM %s WHERE pid > :cursor ORDER BY pid ASC LIMIT %d',
                $table,
                max(1, min($batchSize, 500)),
            ),
            ['cursor' => $cursor],
        );

        $imported = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $sourceId = (string) $row['pid'];

            try {
                $existing = $this->mappings->findTargetId('coppermine', 'picture', $sourceId);
                if ($existing !== null) {
                    ++$skipped;
                    $this->checkpoints->save('coppermine', 'pictures', $sourceId);
                    continue;
                }

                $collectionId = $this->mappings->findTargetId('coppermine', 'album', (string) $row['aid']);
                if ($collectionId === null) {
                    throw new \RuntimeException(sprintf('Album %s has not been imported.', $row['aid']));
                }

                $path = $this->files->locate((string) $row['filepath'], (string) $row['filename']);
                if (!is_file($path) || !is_readable($path)) {
                    throw new \RuntimeException('Original file is missing or unreadable: '.$path);
                }

                $inspection = $this->inspector->inspect($path);
                $mediaId = Uuid::v7();
                $storageKey = sprintf('originals/%s/%s', $mediaId->toRfc4122(), basename((string) $row['filename']));
                $stored = $this->storage->putFile($storageKey, $path);

                $ownerId = (int) $row['owner_id'] > 0
                    ? $this->mappings->findTargetId('coppermine', 'user', (string) $row['owner_id'])
                    : null;

                $mediaType = str_starts_with($inspection->mimeType, 'image/') ? 'image'
                    : (str_starts_with($inspection->mimeType, 'video/') ? 'video'
                    : (str_starts_with($inspection->mimeType, 'audio/') ? 'audio' : 'document'));

                $createdAt = (int) $row['ctime'] > 0
                    ? (new \DateTimeImmutable('@'.(int) $row['ctime']))->setTimezone(new \DateTimeZone('UTC'))
                    : new \DateTimeImmutable();

                $this->target->transactional(function () use ($row, $mediaId, $ownerId, $storageKey, $stored, $inspection, $mediaType, $createdAt, $collectionId, $sourceId): void {
                    $this->target->executeStatement(
                        <<<'SQL'
INSERT INTO media_assets (
    id, owner_id, storage_disk, storage_key, original_filename, mime_type, media_type,
    byte_size, checksum_sha256, width, height, title, description,
    processing_state, moderation_state, metadata, created_at, updated_at
) VALUES (
    :id, :owner_id, 'local', :storage_key, :filename, :mime_type, :media_type,
    :byte_size, :checksum, :width, :height, :title, :description,
    'uploaded', :moderation_state, :metadata::jsonb, :created_at, NOW()
)
SQL,
                        [
                            'id' => $mediaId->toRfc4122(),
                            'owner_id' => $ownerId?->toRfc4122(),
                            'storage_key' => $storageKey,
                            'filename' => (string) $row['filename'],
                            'mime_type' => $inspection->mimeType,
                            'media_type' => $mediaType,
                            'byte_size' => $stored->byteSize,
                            'checksum' => $inspection->sha256,
                            'width' => (int) $row['pwidth'] > 0 ? (int) $row['pwidth'] : null,
                            'height' => (int) $row['pheight'] > 0 ? (int) $row['pheight'] : null,
                            'title' => trim((string) $row['title']) !== '' ? (string) $row['title'] : null,
                            'description' => trim((string) $row['caption']) !== '' ? (string) $row['caption'] : null,
                            'moderation_state' => (string) $row['approved'] === 'YES' ? 'published' : 'pending_review',
                            'metadata' => json_encode([
                                'import' => [
                                    'source' => 'coppermine',
                                    'picture_id' => (int) $row['pid'],
                                    'filepath' => (string) $row['filepath'],
                                    'keywords' => (string) $row['keywords'],
                                ],
                            ], JSON_THROW_ON_ERROR),
                            'created_at' => $createdAt->format(DATE_ATOM),
                        ],
                    );

                    $this->target->executeStatement(
                        <<<'SQL'
INSERT INTO collection_media (collection_id, media_id, position, added_by, created_at)
VALUES (:collection_id, :media_id, :position, :added_by, NOW())
ON CONFLICT (collection_id, media_id) DO NOTHING
SQL,
                        [
                            'collection_id' => $collectionId->toRfc4122(),
                            'media_id' => $mediaId->toRfc4122(),
                            'position' => (int) $row['position'],
                            'added_by' => $ownerId?->toRfc4122(),
                        ],
                    );

                    $this->mappings->remember('coppermine', 'picture', $sourceId, $mediaId);
                });

                $this->checkpoints->save('coppermine', 'pictures', $sourceId);
                ++$imported;
            } catch (\Throwable $e) {
                ++$skipped;
                $warnings[] = sprintf('Picture %s: %s', $sourceId, $e->getMessage());
                // Do not advance the checkpoint past a failed source row. This makes
                // the failure visible and retryable instead of silently losing media.
                break;
            }
        }

        return new CoppermineMediaImportReport($imported, $skipped, $warnings, count($rows) < $batchSize);
    }
}
