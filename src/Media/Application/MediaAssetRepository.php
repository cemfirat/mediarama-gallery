<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaAsset;
use Symfony\Component\Uid\Uuid;

interface MediaAssetRepository
{
    public function save(MediaAsset $media): void;

    public function get(Uuid $id): MediaAsset;
}
