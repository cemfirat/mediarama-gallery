<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaAsset;

interface InspectImageGeometry
{
    /** @return array{width:int,height:int} */
    public function __invoke(MediaAsset $media): array;
}
