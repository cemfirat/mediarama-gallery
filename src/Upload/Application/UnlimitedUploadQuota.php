<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

/**
 * Initial quota adapter. Real per-user/group accounting can replace this
 * without changing upload use cases.
 */
final class UnlimitedUploadQuota implements UploadQuota
{
    public function reserve(Uuid $userId, int $bytes): void
    {
    }

    public function release(Uuid $userId, int $bytes): void
    {
    }
}
