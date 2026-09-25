<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Media;

use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\ModerationState;
use Mediarama\Media\Domain\ProcessingState;
use Mediarama\Media\Domain\StorageObjectId;
use PHPUnit\Framework\TestCase;

final class MediaAssetTest extends TestCase
{
    public function testReadyMediaCanBePublished(): void
    {
        $media = MediaAsset::create(
            null,
            new StorageObjectId('media', 'originals/example/source'),
            'photo.jpg',
            'image/jpeg',
            MediaType::Image,
            1234,
            str_repeat('a', 64),
        );

        self::assertSame(ProcessingState::Processing, $media->processingState);
        self::assertSame(ModerationState::Draft, $media->moderationState);

        $media->markReady();
        $media->publish();

        self::assertSame(ModerationState::Published, $media->moderationState);
    }

    public function testProcessingMediaCannotBePublished(): void
    {
        $media = MediaAsset::create(
            null,
            new StorageObjectId('media', 'originals/example/source'),
            'photo.jpg',
            'image/jpeg',
            MediaType::Image,
            1234,
            str_repeat('a', 64),
        );

        $this->expectException(\DomainException::class);
        $media->publish();
    }
}
