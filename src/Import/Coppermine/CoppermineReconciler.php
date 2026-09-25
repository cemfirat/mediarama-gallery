<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;

final readonly class CoppermineReconciler
{
    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private Connection $target,
        private CoppermineTablePrefix $prefix,
        private CoppermineFileLocator $files,
    ) {
    }

    public function reconcile(int $detailLimit = 200): CoppermineReconciliationReport
    {
        $source = $this->sourceFactory->create();
        $picturesTable = $source->quoteIdentifier($this->prefix->table('pictures'));

        $sourcePictures = (int) $source->fetchOne('SELECT COUNT(*) FROM '.$picturesTable);
        $mappedPictures = (int) $this->target->fetchOne(
            "SELECT COUNT(*) FROM import_mappings WHERE source = 'coppermine' AND entity_type = 'picture'",
        );
        $targetMedia = (int) $this->target->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM media_assets m
JOIN import_mappings i ON i.target_id = m.id
WHERE i.source = 'coppermine' AND i.entity_type = 'picture'
SQL,
        );
        $collectionLinks = (int) $this->target->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM collection_media cm
JOIN import_mappings i ON i.target_id = cm.media_id
WHERE i.source = 'coppermine' AND i.entity_type = 'picture'
SQL,
        );

        $mappedIds = $this->target->fetchFirstColumn(
            "SELECT source_id FROM import_mappings WHERE source = 'coppermine' AND entity_type = 'picture'",
        );
        $mapped = array_fill_keys(array_map('strval', $mappedIds), true);

        $missingFiles = [];
        $unmapped = [];
        $rows = $source->fetchAllAssociative('SELECT pid, filepath, filename FROM '.$picturesTable.' ORDER BY pid ASC');

        foreach ($rows as $row) {
            $pid = (string) $row['pid'];

            if (!isset($mapped[$pid]) && count($unmapped) < $detailLimit) {
                $unmapped[] = $pid;
            }

            $path = $this->files->locate((string) $row['filepath'], (string) $row['filename']);
            if ((!is_file($path) || !is_readable($path)) && count($missingFiles) < $detailLimit) {
                $missingFiles[] = ['pid' => $pid, 'path' => $path];
            }
        }

        return new CoppermineReconciliationReport(
            $sourcePictures,
            $mappedPictures,
            $targetMedia,
            $collectionLinks,
            $missingFiles,
            $unmapped,
        );
    }
}
