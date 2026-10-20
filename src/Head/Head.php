<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

use Contenir\Seo\JsonLd\JsonLd;

/**
 * What SEO adds to one page's head: its title from the site's template,
 * meta tags, and on the home page the site's structured data.
 *
 * @api
 */
final readonly class Head
{
    /**
     * @param list<MetaTag> $tags
     * @param bool          $hidden Whether this copy of the site is hidden from search engines
     */
    public function __construct(
        public string $title,
        public array $tags = [],
        public ?JsonLd $jsonLd = null,
        public bool $hidden = false,
    ) {}
}
