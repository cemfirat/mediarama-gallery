<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;

interface ValidateStoredMediaStructure
{
    public function __invoke(StorageObjectId $object, MediaType $mediaType): void;
}
