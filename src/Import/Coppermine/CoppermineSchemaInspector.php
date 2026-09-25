<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportSource;
use Mediarama\Import\Application\ImportSourceReport;

final readonly class CoppermineSchemaInspector implements ImportSource
{
    public function __construct(
        private Connection $source,
        private string $tablePrefix = 'cpg_',
    ) {
    }

    public function name(): string
    {
        return 'coppermine';
    }

    public function inspect(): ImportSourceReport
    {
        $tables = $this->source->createSchemaManager()->listTableNames();
        $required = ['pictures', 'albums', 'users', 'usergroups', 'comments', 'votes'];
        $warnings = [];
        $counts = [];

        foreach ($required as $suffix) {
            $table = $this->tablePrefix.$suffix;
            if (!in_array($table, $tables, true)) {
                $warnings[] = sprintf('Expected table %s was not found.', $table);
                continue;
            }

            $counts[$suffix] = (int) $this->source->fetchOne('SELECT COUNT(*) FROM '.$this->quoteIdentifier($table));
        }

        $version = '1.6.x-compatible';
        $pictures = $this->tablePrefix.'pictures';

        if (in_array($pictures, $tables, true)) {
            $columns = array_map(
                static fn ($column): string => strtolower($column->getName()),
                $this->source->createSchemaManager()->listTableColumns($pictures),
            );

            if (in_array('mime', $columns, true) || in_array('ftype', $columns, true)) {
                $version = '1.7.x-compatible';
            }
        }

        return new ImportSourceReport('coppermine', $version, $counts, $warnings);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return $this->source->quoteIdentifier($identifier);
    }
}
