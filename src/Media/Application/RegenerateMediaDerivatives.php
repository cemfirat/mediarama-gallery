<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaType;
use Symfony\Component\Uid\Uuid;

final readonly class RegenerateMediaDerivatives
{
    public function __construct(
        private MediaAssetRepository $media,
        private GenerateImageDerivatives $images,
    ) {
    }

    public function __invoke(Uuid $mediaId): void
    {
        $asset = $this->media->get($mediaId);

        if ($asset->mediaType === MediaType::Image) {
            ($this->images)($asset);
        }
    }
}
