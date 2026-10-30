<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

/**
 * What SEO adds to one page's head: its title from the site's template,
 * meta tags, and structured data: the site's on the home page, and the
 * article and breadcrumb trail on others.
 *
 * @api
 */
final readonly class Head
{
    /**
     * @param list<MetaTag>              $tags
     * @param list<array<string, mixed>> $jsonLd The JSON-LD nodes, for a Graph
     * @param bool                       $hidden Whether this copy of the site is hidden from search engines
     */
    public function __construct(
        public string $title,
        public array $tags = [],
        public array $jsonLd = [],
        public bool $hidden = false,
    ) {}
}
