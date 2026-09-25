<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaDerivative;

interface ImageDerivativeGenerator
{
    public function generate(MediaAsset $media, ImageDerivativeProfile $profile, int $processingVersion): MediaDerivative;
}
