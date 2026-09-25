<?php

declare(strict_types=1);

namespace Mediarama\Tests\Unit\Import;

use Mediarama\Import\Coppermine\CoppermineTablePrefix;
use PHPUnit\Framework\TestCase;

final class CoppermineTablePrefixTest extends TestCase
{
    public function testBuildsExpectedTableName(): void
    {
        $prefix = new CoppermineTablePrefix('cpg_');

        self::assertSame('cpg_pictures', $prefix->table('pictures'));
    }

    public function testRejectsUnsafePrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CoppermineTablePrefix('cpg_; DROP TABLE users;');
    }

    public function testRejectsUnsafeSuffix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new CoppermineTablePrefix('cpg_'))->table('../pictures');
    }
}
