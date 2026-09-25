<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

final readonly class CoppermineConnectionFactory
{
    public function __construct(private string $databaseUrl)
    {
    }

    public function create(): Connection
    {
        if ($this->databaseUrl === '') {
            throw new \RuntimeException('COPPERMINE_DATABASE_URL is not configured.');
        }

        $parser = new DsnParser([
            'mysql' => 'pdo_mysql',
            'mariadb' => 'pdo_mysql',
        ]);

        return DriverManager::getConnection($parser->parse($this->databaseUrl));
    }
}
