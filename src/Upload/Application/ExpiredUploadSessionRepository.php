<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use DateTimeImmutable;
use Mediarama\Upload\Domain\UploadSession;

interface ExpiredUploadSessionRepository
{
    /** @return list<UploadSession> */
    public function findExpired(DateTimeImmutable $now, int $limit = 100): array;

    public function delete(UploadSession $session): void;
}
