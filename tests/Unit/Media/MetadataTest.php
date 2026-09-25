<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Media;

use DateTimeImmutable;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\MetadataProvenance;
use Mediarama\Media\Domain\StorageObjectId;
use PHPUnit\Framework\TestCase;

final class MetadataTest extends TestCase
{
    public function testEmbeddedMetadataSeedsCanonicalFieldsAndKeepsProvenance(): void
    {
        $media = MediaAsset::create(
            null,
            new StorageObjectId('media', 'originals/example/source'),
            'photo.jpg',
            'image/jpeg',
            MediaType::Image,
            100,
            str_repeat('b', 64),
        );

        $media->applyEmbeddedMetadata(
            ['exif' => ['Make' => 'Example Camera']],
            [
                'camera_make' => 'Example Camera',
                'captured_at' => new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
            ],
            MetadataProvenance::Embedded,
        );

        self::assertSame('Example Camera', $media->cameraMake);
        self::assertSame('embedded', $media->metadataProvenance['camera_make']);
        self::assertSame('Example Camera', $media->metadata['exif']['Make']);
    }

    public function testUserEditDoesNotOverwriteEmbeddedSnapshot(): void
    {
        $media = MediaAsset::create(
            null,
            new StorageObjectId('media', 'originals/example/source'),
            'photo.jpg',
            'image/jpeg',
            MediaType::Image,
            100,
            str_repeat('c', 64),
        );

        $media->applyEmbeddedMetadata(
            ['iptc' => ['CopyrightNotice' => 'Original copyright']],
            ['copyright' => 'Original copyright'],
            MetadataProvenance::Embedded,
        );

        $media->editMetadata('copyright', 'Updated copyright');

        self::assertSame('Updated copyright', $media->copyright);
        self::assertSame('user', $media->metadataProvenance['copyright']);
        self::assertSame('Original copyright', $media->metadata['iptc']['CopyrightNotice']);
    }
}
