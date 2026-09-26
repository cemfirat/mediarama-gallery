<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Upload;

use Mediarama\Upload\Application\UploadContentPolicy;
use PHPUnit\Framework\TestCase;

final class UploadContentPolicyTest extends TestCase
{
    public function testAcceptsAllowlistedMediaMimeType(): void
    {
        (new UploadContentPolicy(['image/jpeg']))->assertMimeAllowed('image/jpeg');

        self::assertTrue(true);
    }

    public function testRejectsMimeTypeOutsideAllowlist(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('MIME type "text/plain" is not allowed for upload.');

        (new UploadContentPolicy(['image/jpeg']))->assertMimeAllowed('text/plain');
    }

    public function testRejectsGenericDocumentEvenWhenAllowlisted(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Generic document uploads are not enabled.');

        (new UploadContentPolicy(['application/pdf']))->assertMimeAllowed('application/pdf');
    }
}
