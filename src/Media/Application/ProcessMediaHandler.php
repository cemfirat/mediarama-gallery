<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProcessMediaHandler
{
    public function __construct(
        private InspectMediaMetadata $metadata,
        private MediaAssetRepository $media,
    ) {
    }

    public function __invoke(ProcessMedia $message): void
    {
        $id = Uuid::fromString($message->mediaId);

        ($this->metadata)($id);

        // Derivative generation is the next processing stage. Until that
        // adapter is added, metadata completion is sufficient for the
        // foundation pipeline to transition the asset to ready.
        $media = $this->media->get($id);
        $media->markReady();
        $this->media->save($media);
    }
}
