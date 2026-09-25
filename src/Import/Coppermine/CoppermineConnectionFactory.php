<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

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

        return DriverManager::getConnection(['url' => $this->databaseUrl]);
    }
}
