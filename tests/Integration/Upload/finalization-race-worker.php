<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Application\ValidateStoredMediaStructure;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;
use Mediarama\Media\Infrastructure\Persistence\DbalMediaAssetRepository;
use Mediarama\Media\Infrastructure\Storage\LocalMediaStorage;
use Mediarama\Upload\Application\FinalizeUpload;
use Mediarama\Upload\Application\UploadContentPolicy;
use Mediarama\Upload\Application\UploadDestinationAuthorizer;
use Mediarama\Upload\Infrastructure\LocalContentInspector;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadFinalizationClaimRepository;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadFinalizationRepository;
use Mediarama\Upload\Infrastructure\Persistence\DbalUploadSessionRepository;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

if ($argc !== 6) {
    fwrite(STDERR, "Usage: finalization-race-worker.php SESSION_ID USER_ID MEDIA_ROOT BARRIER_DIR DISPATCH_DIR\n");
    exit(64);
}

[, $sessionValue, $userValue, $mediaRoot, $barrierDir, $dispatchDir] = $argv;

$dsn = new DsnParser([
    'postgresql' => 'pdo_pgsql',
    'postgres' => 'pdo_pgsql',
]);
$db = DriverManager::getConnection($dsn->parse((string) getenv('DATABASE_URL')));
$storage = new LocalMediaStorage($mediaRoot);

$structure = new class($barrierDir) implements ValidateStoredMediaStructure {
    public function __construct(private string $barrierDir)
    {
    }

    public function __invoke(StorageObjectId $object, MediaType $mediaType): void
    {
        $marker = $this->barrierDir.'/'.getmypid().'.ready';
        if (file_put_contents($marker, 'ready') === false) {
            throw new RuntimeException('Unable to create finalization race barrier marker.');
        }

        $deadline = microtime(true) + 10.0;
        while (count(glob($this->barrierDir.'/*.ready') ?: []) < 2) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Timed out waiting for concurrent finalizer.');
            }
            usleep(10000);
        }
    }
};

$authorizer = new class implements UploadDestinationAuthorizer {
    public function assertCanUpload(Uuid $userId, ?Uuid $collectionId): void
    {
    }
};

$bus = new class($dispatchDir) implements MessageBusInterface {
    public function __construct(private string $dispatchDir)
    {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $marker = $this->dispatchDir.'/'.getmypid().'-'.bin2hex(random_bytes(4)).'.dispatch';
        if (file_put_contents($marker, $message::class) === false) {
            throw new RuntimeException('Unable to record processing dispatch.');
        }

        return new Envelope($message, $stamps);
    }
};

$finalize = new FinalizeUpload(
    new DbalUploadSessionRepository($db),
    new DbalMediaAssetRepository($db),
    $storage,
    new LocalContentInspector($storage),
    new UploadContentPolicy(['image/png']),
    $structure,
    $authorizer,
    new DbalUploadFinalizationClaimRepository($db),
    new DbalUploadFinalizationRepository($db),
    $bus,
);

$asset = $finalize(
    Uuid::fromString($sessionValue),
    Uuid::fromString($userValue),
);

echo json_encode([
    'media_id' => $asset->id->toRfc4122(),
    'storage_key' => $asset->original->key,
    'checksum' => $asset->checksumSha256,
], JSON_THROW_ON_ERROR).PHP_EOL;
