<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Upload;

use Mediarama\Upload\Domain\UploadSession;
use Mediarama\Upload\Domain\UploadStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UploadSessionTest extends TestCase
{
    public function testHappyPathStateTransitions(): void
    {
        $session = UploadSession::create(Uuid::v7(), null, 'image.jpg', 5000, 'image/jpeg');

        self::assertSame(UploadStatus::Created, $session->status);
        self::assertStringStartsWith('temporary/', $session->temporaryStorageKey);

        $session->begin();
        $session->markUploaded();
        $session->beginFinalization();
        $session->complete();

        self::assertSame(UploadStatus::Completed, $session->status);
    }
}
