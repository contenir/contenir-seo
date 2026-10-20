<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\SiteUrl;

use function array_slice;
use function array_values;
use function explode;

/**
 * Decides whether a page is listed in the sitemap, and how: only pages on
 * the site's own host, outside the workflows the site leaves out, that
 * search engines may index. Locations lose their fragment, and images are
 * made absolute, without repeats, up to Google's limit.
 *
 * @api
 */
final readonly class SitemapFilter
{
    /**
     * The most images Google reads for one URL.
     */
    public const int MAX_IMAGES = 1_000;

    /**
     * @param RobotsDirective $robots The site's default, for pages without their own
     */
    public function __construct(
        private SiteUrl $site,
        private SitemapSettings $settings = new SitemapSettings(),
        private RobotsDirective $robots = RobotsDirective::IndexFollow,
    ) {}

    /**
     * The page's sitemap entry, or null when it isn't listed.
     */
    public function url(Page $page): ?SitemapUrl
    {
        if ($this->settings->excludes($page->workflow)) {
            return null;
        }

        if (! (RobotsDirective::parse($page->robots) ?? $this->robots)->indexes()) {
            return null;
        }

        $location = $this->site->absolute(
            explode(
                separator: '#',
                string: $page->url,
            )[0],
        );
        if (null === $location || ! $this->site->owns($location)) {
            return null;
        }

        return new SitemapUrl($location, $page->modified, $this->images($page));
    }

    /**
     * @return list<string>
     */
    private function images(Page $page): array
    {
        $images = [];
        foreach ($page->images as $image) {
            $url          = $this->site->absolute($image) ?? '';
            $images[$url] = $url;
        }

        unset($images['']);

        return array_slice(array_values($images), offset: 0, length: self::MAX_IMAGES);
    }
}
