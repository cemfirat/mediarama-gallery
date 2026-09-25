<?php

declare(strict_types=1);

namespace Mediarama\Import\Application;

interface ImportCheckpointRepository
{
    public function get(string $source, string $stage): ?string;

    public function save(string $source, string $stage, string $cursor): void;

    public function clear(string $source, string $stage): void;
}
