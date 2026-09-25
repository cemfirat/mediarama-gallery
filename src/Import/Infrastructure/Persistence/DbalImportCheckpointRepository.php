<?php

declare(strict_types=1);

namespace Mediarama\Import\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportCheckpointRepository;

final readonly class DbalImportCheckpointRepository implements ImportCheckpointRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function get(string $source, string $stage): ?string
    {
        $value = $this->connection->fetchOne(
            'SELECT cursor FROM import_checkpoints WHERE source = :source AND stage = :stage',
            ['source' => $source, 'stage' => $stage],
        );

        return $value === false ? null : (string) $value;
    }

    public function save(string $source, string $stage, string $cursor): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO import_checkpoints (source, stage, cursor, updated_at)
VALUES (:source, :stage, :cursor, NOW())
ON CONFLICT (source, stage)
DO UPDATE SET cursor = EXCLUDED.cursor, updated_at = NOW()
SQL,
            ['source' => $source, 'stage' => $stage, 'cursor' => $cursor],
        );
    }

    public function clear(string $source, string $stage): void
    {
        $this->connection->delete('import_checkpoints', ['source' => $source, 'stage' => $stage]);
    }
}
