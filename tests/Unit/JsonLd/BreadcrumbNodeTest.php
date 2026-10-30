<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Seo\JsonLd\BreadcrumbNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BreadcrumbNodeTest extends TestCase
{
    private const string HOME = 'https://www.example.com/';

    /**
     * @return array<string, array{string}>
     */
    public static function homePathProvider(): array
    {
        return [
            'no path'  => [''],
            'the root' => ['/'],
            'slashes'  => ['//'],
        ];
    }

    /**
     * @return array<string, array{string, ?string, list<array{string, string}>}>
     */
    public static function trailProvider(): array
    {
        return [
            'a page named by its title'           => [
                '/work/a-project',
                'A Project',
                [['Example', self::HOME], ['Work', self::HOME . 'work'], ['A Project', self::HOME . 'work/a-project']],
            ],
            'a page without a title'              => [
                '/our-work/the_new-site',
                null,
                [
                    ['Example', self::HOME],
                    ['Our work', self::HOME . 'our-work'],
                    ['The new site', self::HOME . 'our-work/the_new-site'],
                ],
            ],
            'an empty title'                      => [
                '/about',
                '',
                [['Example', self::HOME], ['About', self::HOME . 'about']],
            ],
            'empty segments and a trailing slash' => [
                '/news//an%20article/',
                'An Article',
                [
                    ['Example', self::HOME],
                    ['News', self::HOME . 'news'],
                    ['An Article', self::HOME . 'news/an%20article'],
                ],
            ],
            'an encoded segment'                  => [
                '/caf%C3%A9/menu',
                'Menu',
                [['Example', self::HOME], ['Café', self::HOME . 'caf%C3%A9'], ['Menu', self::HOME . 'caf%C3%A9/menu']],
            ],
        ];
    }

    /**
     * @param list<array{string, string}> $steps
     */
    #[Test]
    #[DataProvider('trailProvider')]
    public function stepsFromTheHomePageThroughEachSegmentOfThePath(string $path, ?string $title, array $steps): void
    {
        $expected = [];
        foreach ($steps as $index => [$name, $item]) {
            $expected[] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $name, 'item' => $item];
        }

        static::assertSame(
            ['@type' => 'BreadcrumbList', 'itemListElement' => $expected],
            BreadcrumbNode::of(self::HOME, 'Example', $path, $title),
        );
    }

    #[Test]
    #[DataProvider('homePathProvider')]
    public function theHomePageHasNoTrail(string $path): void
    {
        static::assertSame([], BreadcrumbNode::of(self::HOME, 'Example', $path, 'Home'));
    }
}
