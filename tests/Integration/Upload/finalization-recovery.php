<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ProcessMedia;
use Mediarama\Media\Application\ValidateStoredMediaStructure;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;
use Mediarama\Media\Infrastructure\Persistence\DbalMediaAssetRepository;
use Mediarama\Media\Infrastructure\Storage\LocalMediaStorage;
use Mediarama\Upload\Application\FinalizeUpload;
use Mediarama\Upload\Application\UploadContentPolicy;
use Mediarama\Upload\Application\UploadDestinationAuthorizer;
use Mediarama\Upload\Domain\UploadSession;
use Mediarama\Upload\Infrastructure\LocalContentInspector;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadFinalizationClaimRepository;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadFinalizationRepository;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadSessionRepository;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Uid\Uuid;

function requireCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function removeTree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }

    @rmdir($path);
}

function writeBytes(MediaStorage $storage, StorageObjectId $id, string $bytes): void
{
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to create upload fixture stream.');
    }

    try {
        fwrite($stream, $bytes);
        rewind($stream);
        $storage->write($id, $stream, 'image/png');
    } finally {
        fclose($stream);
    }
}

function createUploadedSession(
    DbalUploadSessionRepository $sessions,
    LocalMediaStorage $storage,
    Uuid $userId,
    string $bytes,
): UploadSession {
    $session = UploadSession::create(
        $userId,
        null,
        'fixture.png',
        strlen($bytes),
        'image/png',
    );
    $session->markUploaded();
    $sessions->save($session);
    writeBytes(
        $storage,
        new StorageObjectId('media', $session->temporaryStorageKey),
        $bytes,
    );

    return $session;
}

final class AllowAllDestination implements UploadDestinationAuthorizer
{
    public function assertCanUpload(Uuid $userId, ?Uuid $collectionId): void
    {
    }
}

final class NoopStructureValidation implements ValidateStoredMediaStructure
{
    public function __invoke(StorageObjectId $object, MediaType $mediaType): void
    {
    }
}

final class RecordingBus implements MessageBusInterface
{
    public int $dispatches = 0;

    public function __construct(
        private string $directory,
        private bool $failAfterRecord = false,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        ++$this->dispatches;
        $marker = $this->directory.'/'.getmypid().'-'.$this->dispatches.'-'.bin2hex(random_bytes(4)).'.dispatch';
        if (file_put_contents($marker, $message::class) === false) {
            throw new RuntimeException('Unable to record integration-test dispatch.');
        }

        if ($this->failAfterRecord) {
            throw new RuntimeException('Simulated crash after processing dispatch.');
        }

        return new Envelope($message, $stamps);
    }
}

function makeFinalizer(Connection $db, string $mediaRoot, MessageBusInterface $bus): FinalizeUpload
{
    $storage = new LocalMediaStorage($mediaRoot);

    return new FinalizeUpload(
        new DbalUploadSessionRepository($db),
        new DbalMediaAssetRepository($db),
        $storage,
        new LocalContentInspector($storage),
        new UploadContentPolicy(['image/png']),
        new NoopStructureValidation(),
        new AllowAllDestination(),
        new DbalUploadFinalizationClaimRepository($db),
        new DbalUploadFinalizationRepository($db),
        $bus,
    );
}

function assertFinalized(
    Connection $db,
    Uuid $sessionId,
    Uuid $expectedMediaId,
): void {
    $row = $db->fetchAssociative(
        <<<'SQL'
SELECT s.status,
       s.finalization_media_id,
       f.media_id,
       f.processing_dispatched_at
FROM upload_sessions s
JOIN upload_finalizations f ON f.upload_session_id = s.id
WHERE s.id = :id
SQL,
        ['id' => $sessionId->toRfc4122()],
    );

    requireCondition($row !== false, 'Finalization state row is missing.');
    requireCondition((string) $row['status'] === 'completed', 'Upload session was not completed.');
    requireCondition((string) $row['finalization_media_id'] === $expectedMediaId->toRfc4122(), 'Reserved MediaAsset ID changed.');
    requireCondition((string) $row['media_id'] === $expectedMediaId->toRfc4122(), 'Finalization mapping MediaAsset ID changed.');
    requireCondition($row['processing_dispatched_at'] !== null, 'Processing dispatch was not recorded.');
    requireCondition(
        (int) $db->fetchOne('SELECT COUNT(*) FROM media_assets WHERE id = :id', ['id' => $expectedMediaId->toRfc4122()]) === 1,
        'Expected exactly one MediaAsset.',
    );
}

$dsn = new DsnParser([
    'postgresql' => 'pdo_pgsql',
    'postgres' => 'pdo_pgsql',
]);
$db = DriverManager::getConnection($dsn->parse((string) getenv('DATABASE_URL')));

$suffix = bin2hex(random_bytes(8));
$root = sys_get_temp_dir().'/mediarama-finalization-'.$suffix;
$mediaRoot = $root.'/media';
$barrierDir = $root.'/barrier';
$dispatchDir = $root.'/dispatch';
if (!mkdir($barrierDir, 0700, true) && !is_dir($barrierDir)) {
    throw new RuntimeException('Unable to create finalization race barrier directory.');
}
if (!mkdir($dispatchDir, 0700, true) && !is_dir($dispatchDir)) {
    throw new RuntimeException('Unable to create finalization dispatch directory.');
}

$png = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=',
    true,
);
if ($png === false) {
    throw new RuntimeException('Unable to decode PNG fixture.');
}

$userId = Uuid::v7();
$username = 'finalize-'.$suffix;
$now = (new DateTimeImmutable())->format(DATE_ATOM);
$db->insert('users', [
    'id' => $userId->toRfc4122(),
    'username' => $username,
    'status' => 'active',
    'created_at' => $now,
    'updated_at' => $now,
]);

$sessions = new DbalUploadSessionRepository($db);
$storage = new LocalMediaStorage($mediaRoot);
$claims = new DbalUploadFinalizationClaimRepository($db);
$finalizations = new DbalUploadFinalizationRepository($db);
$media = new DbalMediaAssetRepository($db);
$createdMedia = [];

try {
    // Real process race: both requests finish validation before either may claim.
    $raceSession = createUploadedSession($sessions, $storage, $userId, $png);
    $worker = __DIR__.'/finalization-race-worker.php';
    $args = [
        $raceSession->id->toRfc4122(),
        $userId->toRfc4122(),
        $mediaRoot,
        $barrierDir,
        $dispatchDir,
    ];

    $workers = [
        new Process([PHP_BINARY, $worker, ...$args]),
        new Process([PHP_BINARY, $worker, ...$args]),
    ];
    foreach ($workers as $process) {
        $process->setTimeout(30.0);
        $process->start();
    }

    $results = [];
    foreach ($workers as $process) {
        $process->wait();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Concurrent finalizer failed: '.trim($process->getErrorOutput().' '.$process->getOutput()),
            );
        }
        $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
    }

    requireCondition($results[0]['media_id'] === $results[1]['media_id'], 'Concurrent finalizers returned different MediaAsset IDs.');
    requireCondition($results[0]['storage_key'] === $results[1]['storage_key'], 'Concurrent finalizers returned different storage targets.');
    requireCondition($results[0]['checksum'] === $results[1]['checksum'], 'Concurrent finalizers returned different checksums.');

    $raceMediaId = Uuid::fromString((string) $results[0]['media_id']);
    $createdMedia[] = $raceMediaId;
    assertFinalized($db, $raceSession->id, $raceMediaId);
    requireCondition(
        !$storage->exists(new StorageObjectId('media', $raceSession->temporaryStorageKey)),
        'Concurrent finalization left the temporary upload behind.',
    );
    requireCondition(
        $storage->exists(new StorageObjectId('media', (string) $results[0]['storage_key'])),
        'Concurrent finalization did not create the reserved permanent object.',
    );

    // Crash after claim but before storage promotion.
    $beforePromotion = createUploadedSession($sessions, $storage, $userId, $png);
    $beforePromotionId = $claims->claim($beforePromotion->id, Uuid::v7());
    $createdMedia[] = $beforePromotionId;
    $asset = makeFinalizer($db, $mediaRoot, new RecordingBus($dispatchDir))($beforePromotion->id, $userId);
    requireCondition($asset->id->equals($beforePromotionId), 'Retry after claim changed the reserved MediaAsset ID.');
    assertFinalized($db, $beforePromotion->id, $beforePromotionId);

    // Crash after promotion but before MediaAsset persistence.
    $afterPromotion = createUploadedSession($sessions, $storage, $userId, $png);
    $afterPromotionId = $claims->claim($afterPromotion->id, Uuid::v7());
    $createdMedia[] = $afterPromotionId;
    $afterPromotionTarget = new StorageObjectId('media', 'originals/'.$afterPromotionId->toRfc4122().'/source');
    $storage->promote(new StorageObjectId('media', $afterPromotion->temporaryStorageKey), $afterPromotionTarget);
    $asset = makeFinalizer($db, $mediaRoot, new RecordingBus($dispatchDir))($afterPromotion->id, $userId);
    requireCondition($asset->id->equals($afterPromotionId), 'Retry after promotion changed the reserved MediaAsset ID.');
    assertFinalized($db, $afterPromotion->id, $afterPromotionId);

    // Crash after MediaAsset persistence but before finalization mapping.
    $afterMedia = createUploadedSession($sessions, $storage, $userId, $png);
    $afterMediaId = $claims->claim($afterMedia->id, Uuid::v7());
    $createdMedia[] = $afterMediaId;
    $afterMediaTarget = new StorageObjectId('media', 'originals/'.$afterMediaId->toRfc4122().'/source');
    $stored = $storage->promote(new StorageObjectId('media', $afterMedia->temporaryStorageKey), $afterMediaTarget);
    $content = (new LocalContentInspector($storage))->inspect($afterMediaTarget);
    $media->save(MediaAsset::createWithId(
        $afterMediaId,
        $userId,
        $afterMediaTarget,
        $afterMedia->originalFilename,
        $content->mimeType,
        $content->mediaType,
        $stored->byteSize,
        $content->sha256,
    ));
    $asset = makeFinalizer($db, $mediaRoot, new RecordingBus($dispatchDir))($afterMedia->id, $userId);
    requireCondition($asset->id->equals($afterMediaId), 'Retry after MediaAsset persistence changed the reserved ID.');
    assertFinalized($db, $afterMedia->id, $afterMediaId);

    // Crash after mapping but before session completion / dispatch.
    $afterMapping = createUploadedSession($sessions, $storage, $userId, $png);
    $afterMappingId = $claims->claim($afterMapping->id, Uuid::v7());
    $createdMedia[] = $afterMappingId;
    $afterMappingTarget = new StorageObjectId('media', 'originals/'.$afterMappingId->toRfc4122().'/source');
    $stored = $storage->promote(new StorageObjectId('media', $afterMapping->temporaryStorageKey), $afterMappingTarget);
    $content = (new LocalContentInspector($storage))->inspect($afterMappingTarget);
    $media->save(MediaAsset::createWithId(
        $afterMappingId,
        $userId,
        $afterMappingTarget,
        $afterMapping->originalFilename,
        $content->mimeType,
        $content->mediaType,
        $stored->byteSize,
        $content->sha256,
    ));
    $finalizations->remember($afterMapping->id, $afterMappingId);
    $asset = makeFinalizer($db, $mediaRoot, new RecordingBus($dispatchDir))($afterMapping->id, $userId);
    requireCondition($asset->id->equals($afterMappingId), 'Retry after mapping changed the MediaAsset ID.');
    assertFinalized($db, $afterMapping->id, $afterMappingId);

    // A crash after an externally visible dispatch but before the dispatch marker
    // is recorded may duplicate delivery on retry, but must never lose delivery.
    $dispatchCrash = createUploadedSession($sessions, $storage, $userId, $png);
    $crashingBus = new RecordingBus($dispatchDir, true);
    try {
        makeFinalizer($db, $mediaRoot, $crashingBus)($dispatchCrash->id, $userId);
        throw new RuntimeException('Expected simulated dispatch crash.');
    } catch (RuntimeException $error) {
        requireCondition(
            $error->getMessage() === 'Simulated crash after processing dispatch.',
            'Unexpected error during simulated dispatch crash: '.$error->getMessage(),
        );
    }

    $dispatchCrashMediaId = $finalizations->findMediaId($dispatchCrash->id);
    requireCondition($dispatchCrashMediaId !== null, 'Dispatch-crash scenario did not persist finalization mapping.');
    $createdMedia[] = $dispatchCrashMediaId;
    requireCondition(!$finalizations->isProcessingDispatched($dispatchCrash->id), 'Dispatch crash was incorrectly marked as delivered.');

    $retryBus = new RecordingBus($dispatchDir);
    $asset = makeFinalizer($db, $mediaRoot, $retryBus)($dispatchCrash->id, $userId);
    requireCondition($asset->id->equals($dispatchCrashMediaId), 'Dispatch retry changed the MediaAsset ID.');
    requireCondition($retryBus->dispatches === 1, 'Dispatch retry did not deliver processing exactly once in the retry call.');
    assertFinalized($db, $dispatchCrash->id, $dispatchCrashMediaId);

    echo "OK concurrent finalizers converge on one MediaAsset\n";
    echo "OK retry after finalization claim\n";
    echo "OK retry after storage promotion\n";
    echo "OK retry after MediaAsset persistence\n";
    echo "OK retry after finalization mapping\n";
    echo "OK dispatch crash is recovered with at-least-once delivery\n";
} finally {
    foreach ($createdMedia as $mediaId) {
        $db->executeStatement('DELETE FROM media_assets WHERE id = :id', ['id' => $mediaId->toRfc4122()]);
    }
    $db->executeStatement('DELETE FROM upload_sessions WHERE user_id = :id', ['id' => $userId->toRfc4122()]);
    $db->executeStatement('DELETE FROM users WHERE id = :id', ['id' => $userId->toRfc4122()]);
    removeTree($root);
}
