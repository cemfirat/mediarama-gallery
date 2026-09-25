<?php

declare(strict_types=1);

namespace Mediarama\Collection\Application;

use Symfony\Component\Uid\Uuid;

interface CollectionUploadPermissionRepository
{
    public function userCanUpload(Uuid $userId, Uuid $collectionId): bool;
}
