<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Upload;

use DateTimeImmutable;
use Mediarama\Media\Application\MediaAssetRepository;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\StoredObject;
use Mediarama\Media\Application\ValidateStoredMediaStructure;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;
use Mediarama\Upload\Application\ContentInspector;
use Mediarama\Upload\Application\FinalizeUpload;
use Mediarama\Upload\Application\InspectedContent;
use Mediarama\Upload\Application\UploadContentPolicy;
use Mediarama\Upload\Application\UploadDestinationAuthorizer;
use Mediarama\Upload\Application\UploadFinalizationClaimRepository;
use Mediarama\Upload\Application\UploadFinalizationRepository;
use Mediarama\Upload\Application\UploadSessionRepository;
use Mediarama\Upload\Domain\UploadSession;
use Mediarama\Upload\Domain\UploadStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class FinalizeUploadRecoveryTest extends TestCase
{
    public function testExistingMappingCompletesInterruptedSessionAndDispatchesOnlyWhenNeeded(): void
    {
        $userId = Uuid::v7();
        $mediaId = Uuid::v7();
        $session = UploadSession::create($userId, null, 'photo.jpg', 3, 'image/jpeg');
        $session->markUploaded();
        $session->beginFinalization();

        $asset = MediaAsset::createWithId(
            $mediaId,
            $userId,
            new StorageObjectId('media', 'originals/'.$mediaId->toRfc4122().'/source'),
            'photo.jpg',
            'image/jpeg',
            MediaType::Image,
            3,
            str_repeat('a', 64),
        );

        $sessions = new class($session) implements UploadSessionRepository {
            public int $saves = 0;

            public function __construct(private UploadSession $session)
            {
            }

            public function save(UploadSession $session): void
            {
                ++$this->saves;
                $this->session = $session;
            }

            public function get(Uuid $id): UploadSession
            {
                return $this->session;
            }
        };

        $media = new class($asset) implements MediaAssetRepository {
            public function __construct(private MediaAsset $asset)
            {
            }

            public function save(MediaAsset $media): void
            {
                throw new \LogicException('Existing mapped media must not be saved again on the fast path.');
            }

            public function get(Uuid $id): MediaAsset
            {
                return $this->asset;
            }
        };

        $storage = new class implements MediaStorage {
            public function write(StorageObjectId $id, $stream, ?string $contentType = null): StoredObject
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function read(StorageObjectId $id)
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function exists(StorageObjectId $id): bool
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function stat(StorageObjectId $id): StoredObject
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function delete(StorageObjectId $id): void
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function promote(StorageObjectId $temporary, StorageObjectId $permanent): StoredObject
            {
                throw new \LogicException('Storage must not be touched on the mapped fast path.');
            }

            public function publicUrl(StorageObjectId $id): ?string
            {
                return null;
            }

            public function temporaryUrl(StorageObjectId $id, DateTimeImmutable $expiresAt): ?string
            {
                return null;
            }
        };

        $inspector = new class implements ContentInspector {
            public function inspect(StorageObjectId $object): InspectedContent
            {
                throw new \LogicException('Content inspection must not run on the mapped fast path.');
            }
        };

        $structure = new class implements ValidateStoredMediaStructure {
            public function __invoke(StorageObjectId $object, MediaType $mediaType): void
            {
                throw new \LogicException('Structural validation must not run on the mapped fast path.');
            }
        };

        $authorizer = new class implements UploadDestinationAuthorizer {
            public function assertCanUpload(Uuid $userId, ?Uuid $collectionId): void
            {
                throw new \LogicException('Destination authorization must not rerun after durable mapping.');
            }
        };

        $claims = new class implements UploadFinalizationClaimRepository {
            public function findReservedMediaId(Uuid $sessionId): ?Uuid
            {
                throw new \LogicException('Claim lookup must not run on the mapped fast path.');
            }

            public function claim(Uuid $sessionId, Uuid $candidateMediaId): Uuid
            {
                throw new \LogicException('Claim must not run on the mapped fast path.');
            }
        };

        $finalizations = new class($mediaId) implements UploadFinalizationRepository {
            public bool $dispatched = false;
            public int $marks = 0;

            public function __construct(private Uuid $mediaId)
            {
            }

            public function findMediaId(Uuid $sessionId): ?Uuid
            {
                return $this->mediaId;
            }

            public function remember(Uuid $sessionId, Uuid $mediaId): void
            {
                throw new \LogicException('Existing mapping must not be rewritten on the fast path.');
            }

            public function isProcessingDispatched(Uuid $sessionId): bool
            {
                return $this->dispatched;
            }

            public function markProcessingDispatched(Uuid $sessionId): void
            {
                $this->dispatched = true;
                ++$this->marks;
            }
        };

        $bus = new class implements MessageBusInterface {
            public int $dispatches = 0;

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                ++$this->dispatches;

                return new Envelope($message, $stamps);
            }
        };

        $finalize = new FinalizeUpload(
            $sessions,
            $media,
            $storage,
            $inspector,
            new UploadContentPolicy(['image/jpeg']),
            $structure,
            $authorizer,
            $claims,
            $finalizations,
            $bus,
        );

        $first = $finalize($session->id, $userId);

        self::assertSame($mediaId->toRfc4122(), $first->id->toRfc4122());
        self::assertSame(UploadStatus::Completed, $session->status);
        self::assertSame(1, $sessions->saves);
        self::assertSame(1, $bus->dispatches);
        self::assertSame(1, $finalizations->marks);

        $second = $finalize($session->id, $userId);

        self::assertSame($mediaId->toRfc4122(), $second->id->toRfc4122());
        self::assertSame(1, $sessions->saves);
        self::assertSame(1, $bus->dispatches);
        self::assertSame(1, $finalizations->marks);
    }
}
