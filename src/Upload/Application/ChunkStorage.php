<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Upload\Domain\UploadChunk;
use Symfony\Component\Uid\Uuid;

interface ChunkStorage
{
    /** @param resource $stream */
    public function writeChunk(Uuid $sessionId, UploadChunk $chunk, $stream): void;

    /** @return list<UploadChunk> */
    public function listChunks(Uuid $sessionId): array;

    public function assemble(Uuid $sessionId, int $expectedSize, string $targetStorageKey): void;

    public function deleteSessionChunks(Uuid $sessionId): void;
}
