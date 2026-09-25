<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use DateTimeImmutable;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\StorageObjectId;

final readonly class CleanupExpiredUploads
{
    public function __construct(
        private ExpiredUploadSessionRepository $sessions,
        private ChunkStorage $chunks,
        private MediaStorage $storage,
        private UploadQuota $quota,
    ) {
    }

    public function __invoke(int $limit = 100): int
    {
        $expired = $this->sessions->findExpired(new DateTimeImmutable(), $limit);

        foreach ($expired as $session) {
            $this->chunks->deleteSessionChunks($session->id);

            $temporary = new StorageObjectId('media', $session->temporaryStorageKey);
            if ($this->storage->exists($temporary)) {
                $this->storage->delete($temporary);
            }

            $this->quota->release($session->userId, $session->expectedSize);
            $this->sessions->delete($session);
        }

        return count($expired);
    }
}
