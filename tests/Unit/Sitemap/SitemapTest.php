<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sitemap;

use Contenir\Seo\Sitemap\Page;
use Contenir\Seo\Sitemap\Sitemap;
use Contenir\Seo\Sitemap\SitemapFilter;
use Contenir\Seo\Sitemap\SitemapReference;
use Contenir\Seo\Sitemap\SitemapUrl;
use Contenir\Seo\SiteUrl;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

use const DATE_ATOM;

#[Group('unit')]
final class SitemapTest extends TestCase
{
    /**
     * @param list<SitemapUrl> $urls
     *
     * @return list<string>
     */
    private static function locations(array $urls): array
    {
        return array_map(static fn(SitemapUrl $url): string => $url->location, $urls);
    }

    /**
     * @param list<Page>   $pages
     * @param positive-int $size
     */
    private static function sitemap(array $pages, int $size = Sitemap::MAX_URLS): Sitemap
    {
        return new Sitemap($pages, new SitemapFilter(new SiteUrl('https://www.example.com')), $size);
    }

    #[Test]
    public function becomesAnIndexPastItsSize(): void
    {
        $sitemap = self::sitemap([new Page('/a'), new Page('/b'), new Page('/c')], size: 2);

        static::assertSame(
            [
                true,
                2,
                ['https://www.example.com/a', 'https://www.example.com/b'],
                ['https://www.example.com/c'],
                [],
                [],
            ],
            [
                $sitemap->isIndex(),
                $sitemap->pageCount(),
                self::locations($sitemap->page(1)),
                self::locations($sitemap->page(2)),
                $sitemap->page(3),
                $sitemap->page(0),
            ],
        );
    }

    #[Test]
    public function countsAPartPageAsAPage(): void
    {
        $sitemap = self::sitemap([new Page('/a'), new Page('/b'), new Page('/c'), new Page('/d')], size: 3);

        static::assertSame(2, $sitemap->pageCount());
    }

    #[Test]
    public function hasOneEmptyPageWithoutUrls(): void
    {
        $sitemap = self::sitemap([]);

        static::assertSame([false, 1, []], [$sitemap->isIndex(), $sitemap->pageCount(), $sitemap->page(1)]);
    }

    #[Test]
    public function holdsFiftyThousandUrlsInOneFile(): void
    {
        static::assertSame(50_000, Sitemap::MAX_URLS);
    }

    #[Test]
    public function isOneFileUpToItsSize(): void
    {
        $sitemap = self::sitemap([new Page('/a'), new Page('/b')], size: 2);

        static::assertSame([false, 1], [$sitemap->isIndex(), $sitemap->pageCount()]);
    }

    #[Test]
    public function listsEachLocationOnceInTheOrderGiven(): void
    {
        $sitemap = self::sitemap([
            new Page('/b'),
            new Page('/a', new DateTimeImmutable('2026-01-01')),
            new Page('https://www.example.com/b#top', new DateTimeImmutable('2026-02-02')),
            new Page('/hidden', robots: 'noindex'),
        ]);

        static::assertSame(
            [['https://www.example.com/b', 'https://www.example.com/a'], null],
            [self::locations($sitemap->urls), $sitemap->urls[0]->modified],
        );
    }

    #[Test]
    public function referencesEachPageWithItsNewestChange(): void
    {
        $sitemap = self::sitemap(
            [
                new Page('/a', new DateTimeImmutable('2026-03-01T00:00:00+00:00')),
                new Page('/b'),
                new Page('/c', new DateTimeImmutable('2026-05-01T10:00:00+10:00')),
                new Page('/d', new DateTimeImmutable('2026-05-01T00:00:00+00:00')),
                new Page('/e'),
                new Page('/f', new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
                new Page('/g'),
            ],
            size: 2,
        );

        static::assertSame(
            [
                ['https://www.example.com/sitemap-1.xml', '2026-03-01T00:00:00+00:00'],
                ['https://www.example.com/sitemap-2.xml', '2026-05-01T10:00:00+10:00'],
                ['https://www.example.com/sitemap-3.xml', '2026-06-01T00:00:00+00:00'],
                ['https://www.example.com/sitemap-4.xml', null],
            ],
            array_map(
                static fn(SitemapReference $reference): array => [
                    $reference->location,
                    $reference->modified?->format(DATE_ATOM),
                ],
                $sitemap->references('https://www.example.com/sitemap-%d.xml'),
            ),
        );
    }
}
