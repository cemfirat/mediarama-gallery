<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Application\MediaAssetRepository;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ProcessMedia;
use Mediarama\Media\Application\ValidateStoredMediaStructure;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\StorageObjectId;
use Mediarama\Upload\Domain\UploadSession;
use Mediarama\Upload\Domain\UploadStatus;
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
        private ValidateStoredMediaStructure $structureValidator,
        private UploadDestinationAuthorizer $authorizer,
        private UploadFinalizationClaimRepository $claims,
        private UploadFinalizationRepository $finalizations,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(Uuid $sessionId, Uuid $actingUserId): MediaAsset
    {
        $session = $this->sessions->get($sessionId);
        $this->assertOwner($session, $actingUserId);

        $existingMediaId = $this->finalizations->findMediaId($sessionId);
        if ($existingMediaId !== null) {
            $asset = $this->media->get($existingMediaId);
            $this->completeSessionIfNeeded($sessionId);
            $this->dispatchProcessingIfNeeded($sessionId, $asset->id);

            return $asset;
        }

        $reservedMediaId = $this->claims->findReservedMediaId($sessionId);

        if ($reservedMediaId === null) {
            if ($session->isExpired()) {
                throw new \DomainException('Upload session has expired.');
            }

            // Authorization is deliberately repeated before the durable
            // finalization claim. Once claimed, retries are recovery work.
            $this->authorizer->assertCanUpload($actingUserId, $session->targetCollectionId);
        }

        $temporary = new StorageObjectId('media', $session->temporaryStorageKey);
        [$content, $reservedMediaId] = $this->inspectForFinalization(
            $session,
            $temporary,
            $reservedMediaId,
        );

        $mediaId = $reservedMediaId ?? $this->claims->claim($sessionId, Uuid::v7());
        $permanent = $this->permanentObject($mediaId);
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
        $this->completeSessionIfNeeded($sessionId);
        $this->dispatchProcessingIfNeeded($sessionId, $asset->id);

        return $asset;
    }

    private function assertOwner(UploadSession $session, Uuid $actingUserId): void
    {
        if (!$session->userId->equals($actingUserId)) {
            throw new \DomainException('Upload session does not belong to the acting user.');
        }
    }

    /**
     * @return array{0: InspectedContent, 1: ?Uuid}
     */
    private function inspectForFinalization(
        UploadSession $session,
        StorageObjectId $temporary,
        ?Uuid $reservedMediaId,
    ): array {
        try {
            $object = $this->validationObject($temporary, $reservedMediaId);
            $content = $this->inspectAndValidate($session, $object);

            return [$content, $reservedMediaId];
        } catch (\Exception $error) {
            // A concurrent request may have completed validation, reserved the
            // shared MediaAsset ID and promoted the temporary object between
            // our two inspection reads. Recover through that durable target.
            $currentReservation = $this->claims->findReservedMediaId($session->id);
            if ($currentReservation === null) {
                throw $error;
            }

            $permanent = $this->permanentObject($currentReservation);
            if (!$this->storage->exists($permanent)) {
                throw $error;
            }

            return [
                $this->inspectAndValidate($session, $permanent),
                $currentReservation,
            ];
        }
    }

    private function validationObject(StorageObjectId $temporary, ?Uuid $reservedMediaId): StorageObjectId
    {
        if ($this->storage->exists($temporary)) {
            return $temporary;
        }

        if ($reservedMediaId !== null) {
            $permanent = $this->permanentObject($reservedMediaId);
            if ($this->storage->exists($permanent)) {
                return $permanent;
            }
        }

        return $temporary;
    }

    private function inspectAndValidate(UploadSession $session, StorageObjectId $object): InspectedContent
    {
        $content = $this->inspector->inspect($object);
        $this->contentPolicy->assertAllowed($content);

        if ($content->byteSize !== $session->expectedSize) {
            throw new \DomainException('Received upload size does not match expected size.');
        }

        ($this->structureValidator)($object, $content->mediaType);

        return $content;
    }

    private function permanentObject(Uuid $mediaId): StorageObjectId
    {
        return new StorageObjectId('media', sprintf('originals/%s/source', $mediaId->toRfc4122()));
    }

    private function completeSessionIfNeeded(Uuid $sessionId): void
    {
        $session = $this->sessions->get($sessionId);

        if ($session->status === UploadStatus::Completed) {
            return;
        }

        if ($session->status === UploadStatus::Uploaded) {
            // Supports recovery of an older mapping created before durable
            // claim state existed.
            $session->beginFinalization();
        }

        if ($session->status !== UploadStatus::Finalizing) {
            throw new \LogicException('Finalized upload has an incompatible session state.');
        }

        $session->complete();
        $this->sessions->save($session);
    }

    private function dispatchProcessingIfNeeded(Uuid $sessionId, Uuid $mediaId): void
    {
        if ($this->finalizations->isProcessingDispatched($sessionId)) {
            return;
        }

        $this->bus->dispatch(new ProcessMedia($mediaId->toRfc4122()));
        $this->finalizations->markProcessingDispatched($sessionId);
    }
}
