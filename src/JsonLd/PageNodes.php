<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Seo\Head\PageContext;
use Contenir\Seo\SeoConfig;

/**
 * A page's JSON-LD nodes: the site's owner and the site on the home page;
 * elsewhere, the article, when the page is one, and the breadcrumb trail.
 * None without a site URL to identify them by.
 *
 * @internal
 */
final class PageNodes
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function of(SeoConfig $config, PageContext $page): array
    {
        if ($page->home) {
            return (new JsonLd($config->site->origin, $config->siteName(), $config->organisation))->nodes();
        }

        if ('' === $config->site->origin) {
            return [];
        }

        $home  = "{$config->site->origin}/";
        $nodes = null === $page->article
            ? []
            : [ArticleNode::of($page->article, $config->organisation, $home, $config->siteName())];
        $trail = BreadcrumbNode::of($home, $config->siteName(), $page->path, $page->title);

        return [] === $trail ? $nodes : [...$nodes, $trail];
    }
}
