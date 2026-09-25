<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Import;

use Mediarama\Import\Coppermine\CoppermineFileLocator;
use PHPUnit\Framework\TestCase;

final class CoppermineFileLocatorTest extends TestCase
{
    public function testBuildsPathAndSanitizesFilename(): void
    {
        $locator = new CoppermineFileLocator('/srv/coppermine/albums');

        self::assertSame(
            '/srv/coppermine/albums/userpics/10001/photo.jpg',
            $locator->locate('userpics/10001/', '../photo.jpg'),
        );
    }

    public function testRejectsParentTraversalInSourcePath(): void
    {
        $this->expectException(\RuntimeException::class);

        (new CoppermineFileLocator('/srv/coppermine/albums'))
            ->locate('../outside/', 'photo.jpg');
    }

    public function testRejectsEmptyRoot(): void
    {
        $this->expectException(\RuntimeException::class);

        (new CoppermineFileLocator(''))->locate('userpics/', 'photo.jpg');
    }
}
