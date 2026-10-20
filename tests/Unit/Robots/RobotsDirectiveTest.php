<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Robots;

use Contenir\Seo\Robots\RobotsDirective;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RobotsDirectiveTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?RobotsDirective}>
     */
    public static function values(): array
    {
        return [
            'index, follow'    => ['index, follow', RobotsDirective::IndexFollow],
            'all'              => ['all', RobotsDirective::IndexFollow],
            'noindex'          => ['noindex', RobotsDirective::NoindexFollow],
            'NOINDEX, padded'  => [' NOINDEX ,FOLLOW ', RobotsDirective::NoindexFollow],
            'nofollow'         => ['nofollow', RobotsDirective::IndexNofollow],
            'noindex,nofollow' => ['noindex,nofollow', RobotsDirective::NoindexNofollow],
            'none'             => ['none', RobotsDirective::NoindexNofollow],
            'something else'   => ['max-image-preview:large', RobotsDirective::IndexFollow],
            'blank'            => ['', null],
            'null'             => [null, null],
        ];
    }

    #[Test]
    public function offersEachDirectiveWithALabel(): void
    {
        static::assertSame(
            [
                'index, follow'     => 'Show pages in search results and follow their links',
                'noindex, follow'   => 'Keep pages out of search results, but follow their links',
                'index, nofollow'   => 'Show pages in search results, but don\'t follow their links',
                'noindex, nofollow' => 'Keep pages out of search results and don\'t follow their links',
            ],
            RobotsDirective::options(),
        );
    }

    #[DataProvider('values')]
    #[Test]
    public function readsRobotsValuesAsSitesWriteThem(?string $value, ?RobotsDirective $directive): void
    {
        static::assertSame($directive, RobotsDirective::parse($value));
    }

    #[Test]
    public function saysWhetherPagesAreIndexed(): void
    {
        static::assertSame(
            [true, false, true, false],
            [
                RobotsDirective::IndexFollow->indexes(),
                RobotsDirective::NoindexFollow->indexes(),
                RobotsDirective::IndexNofollow->indexes(),
                RobotsDirective::NoindexNofollow->indexes(),
            ],
        );
    }
}
