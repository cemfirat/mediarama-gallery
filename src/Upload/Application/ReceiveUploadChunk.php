<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Upload\Domain\UploadChunk;
use Mediarama\Upload\Domain\UploadStatus;
use Symfony\Component\Uid\Uuid;

final readonly class ReceiveUploadChunk
{
    public function __construct(
        private UploadSessionRepository $sessions,
        private ChunkStorage $chunks,
        private UploadPolicy $policy,
    ) {
    }

    /** @param resource $stream */
    public function __invoke(
        Uuid $sessionId,
        Uuid $actingUserId,
        UploadChunk $chunk,
        $stream,
    ): void {
        $session = $this->sessions->get($sessionId);

        if (!$session->userId->equals($actingUserId)) {
            throw new \DomainException('Upload session does not belong to the acting user.');
        }

        if ($session->isExpired()) {
            throw new \DomainException('Upload session has expired.');
        }

        if (!in_array($session->status, [UploadStatus::Created, UploadStatus::Uploading], true)) {
            throw new \DomainException('Upload session is not accepting chunks.');
        }

        $this->policy->assertChunkSize($chunk->size);

        if ($session->status === UploadStatus::Created) {
            $session->begin();
            $this->sessions->save($session);
        }

        $this->chunks->writeChunk($sessionId, $chunk, $stream);
    }
}
