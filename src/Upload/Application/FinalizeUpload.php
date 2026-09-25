<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Application\MediaAssetRepository;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ProcessMedia;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class FinalizeUpload
{
    public function __construct(
        private UploadSessionRepository $sessions,
        private MediaAssetRepository $media,
        private MediaStorage $storage,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(
        Uuid $sessionId,
        Uuid $actingUserId,
        string $detectedMime,
        MediaType $mediaType,
        string $sha256,
    ): MediaAsset {
        $session = $this->sessions->get($sessionId);

        if (!$session->userId->equals($actingUserId)) {
            throw new \DomainException('Upload session does not belong to the acting user.');
        }

        if ($session->isExpired()) {
            throw new \DomainException('Upload session has expired.');
        }

        $session->beginFinalization();

        $temporary = new StorageObjectId('media', $session->temporaryStorageKey);
        $received = $this->storage->stat($temporary);

        if ($received->byteSize !== $session->expectedSize) {
            throw new \DomainException('Received upload size does not match expected size.');
        }

        $mediaId = Uuid::v7();
        $permanent = new StorageObjectId('media', sprintf('originals/%s/source', $mediaId->toRfc4122()));
        $stored = $this->storage->promote($temporary, $permanent);

        $asset = MediaAsset::createWithId(
            $mediaId,
            $actingUserId,
            $permanent,
            $session->originalFilename,
            $detectedMime,
            $mediaType,
            $stored->byteSize,
            $sha256,
        );

        $this->media->save($asset);
        $session->complete();
        $this->sessions->save($session);

        $this->bus->dispatch(new ProcessMedia($asset->id->toRfc4122()));

        return $asset;
    }
}
