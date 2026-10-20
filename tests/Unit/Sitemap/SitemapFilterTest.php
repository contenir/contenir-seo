<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sitemap;

use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\Sitemap\Page;
use Contenir\Seo\Sitemap\SitemapFilter;
use Contenir\Seo\Sitemap\SitemapSettings;
use Contenir\Seo\SiteUrl;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function count;
use function range;

#[Group('unit')]
final class SitemapFilterTest extends TestCase
{
    /**
     * @return array<string, array{Page}>
     */
    public static function leftOut(): array
    {
        return [
            'a workflow left out' => [new Page('/old', workflow: 'Redirect')],
            'noindex'             => [new Page('/thanks', robots: 'noindex')],
            'none'                => [new Page('/thanks', robots: 'none')],
            'another host'        => [new Page('https://shop.example.com/')],
            'another scheme'      => [new Page('mailto:hello@example.com')],
            'a relative path'     => [new Page('about')],
        ];
    }

    private static function filter(): SitemapFilter
    {
        return new SitemapFilter(
            new SiteUrl('https://www.example.com'),
            new SitemapSettings(excluded: ['redirect']),
        );
    }

    #[Test]
    public function capsImagesAtGooglesLimit(): void
    {
        $images = array_map(static fn(int $number): string => "/img/{$number}.jpg", range(
            start: 1,
            end: 1_001,
        ));

        $url = self::filter()->url(new Page('/gallery', images: $images));

        static::assertSame(
            [1_000, 'https://www.example.com/img/1000.jpg'],
            [count($url->images ?? []), $url->images[999] ?? null],
        );
    }

    #[DataProvider('leftOut')]
    #[Test]
    public function leavesOutPagesThatAreNotListed(Page $page): void
    {
        static::assertNull(self::filter()->url($page));
    }

    #[Test]
    public function leavesOutPagesUnderASiteDefaultOfNoindex(): void
    {
        $filter = new SitemapFilter(new SiteUrl('https://www.example.com'), robots: RobotsDirective::NoindexFollow);

        static::assertSame(
            [null, 'https://www.example.com/kept'],
            [$filter->url(new Page('/hidden'))?->location, $filter->url(new Page('/kept', robots: 'index'))?->location],
        );
    }

    #[Test]
    public function listsAPageOnTheSitesHostWithItsDateAndImages(): void
    {
        $modified = new DateTimeImmutable('2026-10-01T09:30:00+10:00');

        $url = self::filter()->url(new Page(
            'https://www.example.com/about#team',
            $modified,
            ['/img/a.jpg', 'https://cdn.example.net/b.jpg', '/img/a.jpg', 'javascript:alert(1)', ''],
            robots: 'index, nofollow',
            workflow: 'page',
        ));

        static::assertSame(
            [
                'https://www.example.com/about',
                $modified,
                ['https://www.example.com/img/a.jpg', 'https://cdn.example.net/b.jpg'],
            ],
            [$url?->location, $url?->modified, $url?->images],
        );
    }

    #[Test]
    public function listsPagesWithDefaultsWhenGivenOnlyTheSiteUrl(): void
    {
        $url = (new SitemapFilter(new SiteUrl('https://www.example.com')))->url(new Page('/', workflow: 'redirect'));

        static::assertSame(['https://www.example.com/', null, []], [$url?->location, $url?->modified, $url?->images]);
    }
}
