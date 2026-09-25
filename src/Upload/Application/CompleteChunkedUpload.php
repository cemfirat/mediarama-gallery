<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

final readonly class CompleteChunkedUpload
{
    public function __construct(
        private UploadSessionRepository $sessions,
        private ChunkStorage $chunks,
    ) {
    }

    public function __invoke(Uuid $sessionId, Uuid $actingUserId): void
    {
        $session = $this->sessions->get($sessionId);

        if (!$session->userId->equals($actingUserId)) {
            throw new \DomainException('Upload session does not belong to the acting user.');
        }

        if ($session->isExpired()) {
            throw new \DomainException('Upload session has expired.');
        }

        $this->chunks->assemble(
            $sessionId,
            $session->expectedSize,
            $session->temporaryStorageKey,
        );

        $session->markUploaded();
        $this->sessions->save($session);

        $this->chunks->deleteSessionChunks($sessionId);
    }
}
