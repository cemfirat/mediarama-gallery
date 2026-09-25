<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;

interface ContentInspector
{
    public function inspect(StorageObjectId $object): InspectedContent;
}
