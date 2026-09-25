<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\StorageObjectId;

interface MediaMetadataInspector
{
    public function inspect(StorageObjectId $original): InspectedMetadata;
}
