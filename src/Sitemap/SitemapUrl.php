<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use DateTimeInterface;

/**
 * One <url> of a sitemap: an absolute location on the site, when it last
 * changed and its images. There is no changefreq or priority: Google
 * ignores both.
 *
 * @api
 */
final readonly class SitemapUrl
{
    /**
     * @param list<string> $images Absolute image URLs
     */
    public function __construct(
        public string $location,
        public ?DateTimeInterface $modified = null,
        public array $images = [],
    ) {}
}
