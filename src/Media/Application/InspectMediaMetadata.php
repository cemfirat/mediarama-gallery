<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Symfony\Component\Uid\Uuid;

final readonly class InspectMediaMetadata
{
    public function __construct(
        private MediaAssetRepository $media,
        private MediaMetadataInspector $inspector,
        private ApplyInspectedMetadata $apply,
    ) {
    }

    public function __invoke(Uuid $mediaId): void
    {
        $media = $this->media->get($mediaId);
        $metadata = $this->inspector->inspect($media->original);

        ($this->apply)($media, $metadata);
        $this->media->save($media);
    }
}
