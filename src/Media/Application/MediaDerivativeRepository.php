<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaDerivative;
use Symfony\Component\Uid\Uuid;

interface MediaDerivativeRepository
{
    public function save(MediaDerivative $derivative): void;

    public function find(Uuid $mediaId, string $kind, string $profile, int $processingVersion): ?MediaDerivative;
}
