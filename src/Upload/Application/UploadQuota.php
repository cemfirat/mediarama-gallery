<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

interface UploadQuota
{
    public function reserve(Uuid $userId, int $bytes): void;

    public function release(Uuid $userId, int $bytes): void;
}
