<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Media;

use Mediarama\Media\Infrastructure\Metadata\ExifToolMetadataParser;
use PHPUnit\Framework\TestCase;

final class ExifToolMetadataParserTest extends TestCase
{
    public function testParsesAndPreservesGroupedMetadata(): void
    {
        $json = json_encode([[
            'SourceFile' => '/tmp/photo.jpg',
            'EXIF:Make' => 'NIKON CORPORATION',
            'EXIF:Model' => 'NIKON Z 8',
            'EXIF:ISO' => 100,
            'EXIF:FNumber' => 2.8,
            'EXIF:ExposureTime' => '1/250',
            'EXIF:FocalLength' => '50 mm',
            'IPTC:CopyrightNotice' => 'Example',
            'XMP:Subject' => ['portrait', 'vienna'],
            'Composite:GPSLatitude' => 48.2082,
            'Composite:GPSLongitude' => 16.3738,
        ]], JSON_THROW_ON_ERROR);

        $metadata = (new ExifToolMetadataParser())->parse($json);

        self::assertSame('NIKON CORPORATION', $metadata->cameraMake);
        self::assertSame('NIKON Z 8', $metadata->cameraModel);
        self::assertSame(100, $metadata->iso);
        self::assertSame(['portrait', 'vienna'], $metadata->keywords);
        self::assertSame('Example', $metadata->copyright);
        self::assertSame(48.2082, $metadata->latitude);
        self::assertSame('NIKON CORPORATION', $metadata->embedded['exif']['Make']);
    }
}
