<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use DateTimeImmutable;
use Mediarama\Media\Domain\StorageObjectId;

interface MediaStorage
{
    /** @param resource $stream */
    public function write(StorageObjectId $id, $stream, ?string $contentType = null): StoredObject;

    /** @return resource */
    public function read(StorageObjectId $id);

    public function exists(StorageObjectId $id): bool;

    public function stat(StorageObjectId $id): StoredObject;

    public function delete(StorageObjectId $id): void;

    public function promote(StorageObjectId $temporary, StorageObjectId $permanent): StoredObject;

    public function publicUrl(StorageObjectId $id): ?string;

    public function temporaryUrl(StorageObjectId $id, DateTimeImmutable $expiresAt): ?string;
}
