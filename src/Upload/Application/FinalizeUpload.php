<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Application\MediaAssetRepository;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ProcessMedia;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\StorageObjectId;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FinalizeUpload
{
    public function __construct(
        private UploadSessionRepository $sessions,
        private MediaAssetRepository $media,
        private MediaStorage $storage,
        private ContentInspector $inspector,
        private UploadContentPolicy $contentPolicy,
        private UploadDestinationAuthorizer $authorizer,
        private UploadFinalizationRepository $finalizations,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(Uuid $sessionId, Uuid $actingUserId): MediaAsset
    {
        $session = $this->sessions->get($sessionId);

        $existingMediaId = $this->finalizations->findMediaId($sessionId);
        if ($existingMediaId !== null) {
            if (!$session->userId->equals($actingUserId)) {
                throw new \DomainException('Upload session does not belong to the acting user.');
            }

            return $this->media->get($existingMediaId);
        }

        if (!$session->userId->equals($actingUserId)) {
            throw new \DomainException('Upload session does not belong to the acting user.');
        }

        if ($session->isExpired()) {
            throw new \DomainException('Upload session has expired.');
        }

        // Authorization is deliberately repeated at finalization time. A user
        // may have lost access to the destination after creating the session.
        $this->authorizer->assertCanUpload($actingUserId, $session->targetCollectionId);

        $temporary = new StorageObjectId('media', $session->temporaryStorageKey);
        $content = $this->inspector->inspect($temporary);
        $this->contentPolicy->assertAllowed($content);

        if ($content->byteSize !== $session->expectedSize) {
            throw new \DomainException('Received upload size does not match expected size.');
        }

        $session->beginFinalization();
        $this->sessions->save($session);

        $mediaId = Uuid::v7();
        $permanent = new StorageObjectId('media', sprintf('originals/%s/source', $mediaId->toRfc4122()));
        $stored = $this->storage->promote($temporary, $permanent);

        $asset = MediaAsset::createWithId(
            $mediaId,
            $actingUserId,
            $permanent,
            $session->originalFilename,
            $content->mimeType,
            $content->mediaType,
            $stored->byteSize,
            $content->sha256,
        );

        $this->media->save($asset);
        $this->finalizations->remember($sessionId, $asset->id);
        $session->complete();
        $this->sessions->save($session);

        $this->bus->dispatch(new ProcessMedia($asset->id->toRfc4122()));

        return $asset;
    }
}
