<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use DateTimeInterface;

/**
 * A page a site offers for its sitemap, as a navigation tree or another
 * source describes it. Sitemap decides which pages are listed.
 *
 * @api
 */
final readonly class Page
{
    /**
     * @param string             $url      The page's URL: absolute, or a path on the site
     * @param ?DateTimeInterface $modified When the page's record last changed
     * @param list<string>       $images   The page's images: absolute URLs, or paths on the site
     * @param ?string            $robots   The page's own robots value ("noindex"), when it has one
     * @param ?string            $workflow The page's workflow (page, news, redirect…), which a site can leave out
     */
    public function __construct(
        public string $url,
        public ?DateTimeInterface $modified = null,
        public array $images = [],
        public ?string $robots = null,
        public ?string $workflow = null,
    ) {}
}
