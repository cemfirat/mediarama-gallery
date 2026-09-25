<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Upload;

use Mediarama\Upload\Application\UploadPolicy;
use PHPUnit\Framework\TestCase;

final class UploadPolicyTest extends TestCase
{
    public function testAcceptsValuesWithinLimits(): void
    {
        $policy = new UploadPolicy(1000, 100);
        $policy->assertAssetSize(1000);
        $policy->assertChunkSize(100);

        self::assertTrue(true);
    }

    public function testRejectsOversizedAsset(): void
    {
        $this->expectException(\DomainException::class);
        (new UploadPolicy(1000, 100))->assertAssetSize(1001);
    }

    public function testRejectsOversizedChunk(): void
    {
        $this->expectException(\DomainException::class);
        (new UploadPolicy(1000, 100))->assertChunkSize(101);
    }
}
