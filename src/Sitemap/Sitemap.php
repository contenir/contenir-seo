<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use DateTimeInterface;

use function array_key_exists;
use function array_slice;
use function array_values;
use function ceil;
use function count;
use function max;
use function sprintf;

/**
 * The URLs a site lists in its sitemap, chosen from the pages it offers:
 * every page on the site's own host, hidden from the menu or not, except
 * those in a workflow the site leaves out and those search engines may not
 * index. Past 50,000 URLs the sitemap is split into pages, listed by an
 * index.
 *
 * @api
 */
final readonly class Sitemap
{
    /**
     * The most URLs one sitemap file may list.
     */
    public const int MAX_URLS = 50_000;

    /**
     * @var list<SitemapUrl>
     */
    public array $urls;

    /**
     * @param iterable<Page> $pages
     * @param positive-int   $size  The most URLs in one file
     */
    public function __construct(
        iterable $pages,
        SitemapFilter $filter,
        private int $size = self::MAX_URLS,
    ) {
        $urls = [];
        foreach ($pages as $page) {
            $url = $filter->url($page);
            if (null !== $url && ! array_key_exists($url->location, $urls)) {
                $urls[$url->location] = $url;
            }
        }

        $this->urls = array_values($urls);
    }

    /**
     * @param list<SitemapUrl> $urls
     */
    private static function newest(array $urls): ?DateTimeInterface
    {
        $newest = null;
        foreach ($urls as $url) {
            if (null === $url->modified || null !== $newest && $url->modified <= $newest) {
                continue;
            }

            $newest = $url->modified;
        }

        return $newest;
    }

    /**
     * Whether the sitemap is too large for one file, so /sitemap.xml is an
     * index of its pages.
     */
    public function isIndex(): bool
    {
        return count($this->urls) > $this->size;
    }

    /**
     * The URLs on page $number, counting from 1; none past the last page.
     *
     * @return list<SitemapUrl>
     */
    public function page(int $number): array
    {
        return $number < 1 ? [] : array_slice($this->urls, offset: ($number - 1) * $this->size, length: $this->size);
    }

    /**
     * How many pages the sitemap has; at least one, which may be empty.
     */
    public function pageCount(): int
    {
        return max(1, (int) ceil(count($this->urls) / $this->size));
    }

    /**
     * The index's entries, one per page, each located by $pattern with the
     * page's number for %d (https://www.example.com/sitemap-%d.xml).
     *
     * @return list<SitemapReference>
     */
    public function references(string $pattern): array
    {
        $references = [];
        for ($number = 1; $number <= $this->pageCount(); ++$number) {
            $references[] = new SitemapReference(sprintf($pattern, $number), self::newest($this->page($number)));
        }

        return $references;
    }
}
