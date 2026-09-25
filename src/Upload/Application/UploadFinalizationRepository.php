<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

interface UploadFinalizationRepository
{
    public function findMediaId(Uuid $sessionId): ?Uuid;

    public function remember(Uuid $sessionId, Uuid $mediaId): void;
}
