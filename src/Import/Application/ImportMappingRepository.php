<?php

declare(strict_types=1);

namespace Mediarama\Import\Application;

use Symfony\Component\Uid\Uuid;

interface ImportMappingRepository
{
    public function findTargetId(string $source, string $entityType, string $sourceId): ?Uuid;

    public function remember(string $source, string $entityType, string $sourceId, Uuid $targetId): void;
}
