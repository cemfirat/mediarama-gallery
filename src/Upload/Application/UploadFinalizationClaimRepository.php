<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

interface UploadFinalizationClaimRepository
{
    public function findReservedMediaId(Uuid $sessionId): ?Uuid;

    public function claim(Uuid $sessionId, Uuid $candidateMediaId): Uuid;
}
