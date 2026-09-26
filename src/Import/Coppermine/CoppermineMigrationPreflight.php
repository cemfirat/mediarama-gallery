<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Upload\Application\UploadContentPolicy;

final readonly class CoppermineMigrationPreflight
{
    private const DETAIL_LIMIT = 20;
    private const MIME_PREFIX_BYTES = 262144;

    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private CoppermineTablePrefix $prefix,
        private CoppermineFileLocator $files,
        private UploadContentPolicy $contentPolicy,
    ) {
    }

    public function inspect(): CoppermineMigrationPreflightReport
    {
        $source = $this->sourceFactory->create();
        $blockers = array_merge(
            $this->duplicateEmailBlockers($source),
            $this->bridgeBlockers($source),
            $this->moderatorGroupBlockers($source),
            $this->sourceMediaBlockers($source),
        );

        return new CoppermineMigrationPreflightReport($blockers);
    }

    /** @return list<string> */
    private function duplicateEmailBlockers(Connection $source): array
    {
        $table = $source->quoteIdentifier($this->prefix->table('users'));
        $rows = $source->fetchAllAssociative(sprintf(
            <<<'SQL'
SELECT LOWER(TRIM(user_email)) AS normalized_email, COUNT(*) AS duplicate_count
FROM %s
WHERE TRIM(user_email) <> ''
GROUP BY LOWER(TRIM(user_email))
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC, normalized_email ASC
LIMIT %d
SQL,
            $table,
            self::DETAIL_LIMIT,
        ));

        $blockers = [];
        foreach ($rows as $row) {
            $blockers[] = sprintf(
                'Duplicate normalized email address "%s" is used by %d source users; Mediarama requires unique non-null email addresses.',
                (string) $row['normalized_email'],
                (int) $row['duplicate_count'],
            );
        }

        return $blockers;
    }

    /** @return list<string> */
    private function bridgeBlockers(Connection $source): array
    {
        $configTable = $source->quoteIdentifier($this->prefix->table('config'));
        $rawValue = $source->fetchOne(
            'SELECT value FROM '.$configTable.' WHERE name = :name',
            ['name' => 'bridge_enable'],
        );

        if ($rawValue === false) {
            return ['Required Coppermine config key "bridge_enable" is missing; identity authority cannot be determined safely.'];
        }

        $value = trim((string) $rawValue);
        if ($value === '0') {
            return [];
        }

        if ($value !== '1') {
            return [sprintf(
                'Coppermine config key "bridge_enable" has unsupported value "%s"; expected 0 or 1.',
                $value,
            )];
        }

        $bridgeName = null;
        $schema = $source->createSchemaManager();
        $bridgeTableName = $this->prefix->table('bridge');

        if (in_array($bridgeTableName, $schema->listTableNames(), true)) {
            $bridgeTable = $source->quoteIdentifier($bridgeTableName);
            $shortName = $source->fetchOne(
                'SELECT value FROM '.$bridgeTable.' WHERE name = :name',
                ['name' => 'short_name'],
            );

            if ($shortName !== false && trim((string) $shortName) !== '') {
                $bridgeName = trim((string) $shortName);
            }
        }

        return [sprintf(
            'Coppermine bridging is enabled%s; the local users table cannot be assumed to be the authoritative identity source.',
            $bridgeName !== null ? sprintf(' for "%s"', $bridgeName) : '',
        )];
    }

    /** @return list<string> */
    private function moderatorGroupBlockers(Connection $source): array
    {
        $schema = $source->createSchemaManager();
        $albumsTableName = $this->prefix->table('albums');
        $columns = array_map(
            static fn ($column): string => strtolower($column->getName()),
            $schema->listTableColumns($albumsTableName),
        );

        if (!in_array('moderator_group', $columns, true)) {
            return [];
        }

        $table = $source->quoteIdentifier($albumsTableName);
        $rows = $source->fetchAllAssociative(sprintf(
            'SELECT aid, moderator_group FROM %s WHERE moderator_group <> 0 ORDER BY aid ASC LIMIT %d',
            $table,
            self::DETAIL_LIMIT,
        ));

        $blockers = [];
        foreach ($rows as $row) {
            $blockers[] = sprintf(
                'Album %s has legacy moderator_group=%d; this residue requires explicit review before ACL migration.',
                (string) $row['aid'],
                (int) $row['moderator_group'],
            );
        }

        return $blockers;
    }

    /** @return list<string> */
    private function sourceMediaBlockers(Connection $source): array
    {
        $table = $source->quoteIdentifier($this->prefix->table('pictures'));
        $rows = $source->iterateAssociative(
            'SELECT pid, filepath, filename FROM '.$table.' ORDER BY pid ASC',
        );
        $blockers = [];

        foreach ($rows as $row) {
            if (count($blockers) >= self::DETAIL_LIMIT) {
                break;
            }

            $sourceId = (string) $row['pid'];

            try {
                $path = $this->files->locate((string) $row['filepath'], (string) $row['filename']);
            } catch (\Throwable $e) {
                $blockers[] = sprintf('Picture %s source path is unsafe: %s', $sourceId, $e->getMessage());
                continue;
            }

            if (!is_file($path) || !is_readable($path)) {
                $blockers[] = sprintf('Picture %s original file is missing or unreadable.', $sourceId);
                continue;
            }

            try {
                $this->contentPolicy->assertMimeAllowed($this->detectMimeType($path));
            } catch (\Throwable $e) {
                $blockers[] = sprintf('Picture %s source media is not importable: %s', $sourceId, $e->getMessage());
            }
        }

        return $blockers;
    }

    private function detectMimeType(string $path): string
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open source media for MIME inspection.');
        }

        try {
            $prefix = fread($stream, self::MIME_PREFIX_BYTES);
            if ($prefix === false) {
                throw new \RuntimeException('Unable to read source media for MIME inspection.');
            }
        } finally {
            fclose($stream);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($prefix);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }
}
