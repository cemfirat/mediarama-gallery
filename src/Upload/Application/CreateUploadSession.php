<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Upload\Domain\UploadSession;
use Symfony\Component\Uid\Uuid;

final readonly class CreateUploadSession
{
    public function __construct(
        private UploadSessionRepository $sessions,
        private UploadDestinationAuthorizer $authorizer,
    ) {
    }

    public function __invoke(
        Uuid $userId,
        ?Uuid $targetCollectionId,
        string $originalFilename,
        int $expectedSize,
        ?string $expectedMime = null,
    ): UploadSession {
        $this->authorizer->assertCanUpload($userId, $targetCollectionId);

        $session = UploadSession::create(
            $userId,
            $targetCollectionId,
            $originalFilename,
            $expectedSize,
            $expectedMime,
        );

        $this->sessions->save($session);

        return $session;
    }
}
