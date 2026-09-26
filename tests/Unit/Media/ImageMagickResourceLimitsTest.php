<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Media;

use Mediarama\Media\Infrastructure\Image\ImageMagickResourceLimits;
use PHPUnit\Framework\TestCase;

final class ImageMagickResourceLimitsTest extends TestCase
{
    public function testBuildsFiniteCommandLimitsAndListLengthEnvironment(): void
    {
        $limits = new ImageMagickResourceLimits(
            memory: '64MiB',
            map: '128MiB',
            disk: '256MiB',
            area: '64MP',
            width: 4096,
            height: 3072,
            files: 32,
            threads: 2,
            timeSeconds: 20,
            listLength: 8,
        );

        self::assertSame([
            '-limit', 'memory', '64MiB',
            '-limit', 'map', '128MiB',
            '-limit', 'disk', '256MiB',
            '-limit', 'area', '64MP',
            '-limit', 'width', '4096',
            '-limit', 'height', '3072',
            '-limit', 'file', '32',
            '-limit', 'thread', '2',
            '-limit', 'time', '20',
        ], $limits->commandArguments());

        self::assertSame([
            'MAGICK_LIST_LENGTH_LIMIT' => '8',
        ], $limits->environment());
    }

    public function testRejectsUnlimitedByteLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a finite byte value');

        new ImageMagickResourceLimits(memory: 'unlimited');
    }

    public function testRejectsInvalidAreaLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('area limit must be a finite byte or pixel-cache area value');

        new ImageMagickResourceLimits(area: 'unlimited');
    }

    public function testRejectsNonPositiveNumericLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('width limit must be positive');

        new ImageMagickResourceLimits(width: 0);
    }
}
