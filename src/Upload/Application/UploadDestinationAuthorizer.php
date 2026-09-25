<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

interface UploadDestinationAuthorizer
{
    public function assertCanUpload(Uuid $userId, ?Uuid $collectionId): void;
}
