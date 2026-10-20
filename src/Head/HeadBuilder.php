<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

use Contenir\Seo\JsonLd\JsonLd;
use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\SeoConfig;
use Contenir\Seo\Verification\Verifier;

/**
 * Works out what SEO adds to a page's head from the site's settings:
 *
 * - the title, built by the site's template;
 * - og:site_name;
 * - a robots meta tag, when this copy is hidden or the site's default
 *   isn't to index and follow;
 * - on the home page, the verification tags and the JSON-LD graph;
 * - the default description and share image, for a page without its own.
 *
 * @api
 */
final readonly class HeadBuilder
{
    public function __construct(
        private SeoConfig $config,
    ) {}

    public function build(PageContext $page): Head
    {
        $config = $this->config;
        $hidden = $config->visibility->hides($config->site, $page->host);
        $tags   = [];
        if ('' !== $config->siteName()) {
            $tags[] = MetaTag::property('og:site_name', $config->siteName());
        }

        $robots = $hidden ? RobotsDirective::NoindexNofollow : $config->robots;
        if (RobotsDirective::IndexFollow !== $robots) {
            $tags[] = MetaTag::name('robots', $robots->value);
        }

        if ($page->home) {
            $tags = [...$tags, ...$this->verification()];
        }

        if (! $page->hasDescription && '' !== $config->description) {
            $tags[] = MetaTag::name('description', $config->description);
            $tags[] = MetaTag::property('og:description', $config->description);
            $tags[] = MetaTag::name('twitter:description', $config->description);
        }

        if (! $page->hasImage && null !== $config->shareImage) {
            $tags = [...$tags, ...$config->shareImage->tags()];
        }

        return new Head(
            $page->home ? $config->title->home($page->title) : $config->title->format($page->title),
            $tags,
            $page->home ? new JsonLd($config->site->origin, $config->siteName(), $config->organisation) : null,
            $hidden,
        );
    }

    /**
     * @return list<MetaTag>
     */
    private function verification(): array
    {
        $tags = [];
        foreach (Verifier::cases() as $verifier) {
            $token = $this->config->verification[$verifier->value] ?? null;
            if (null !== $token) {
                $tags[] = MetaTag::name($verifier->metaName(), $token);
            }
        }

        return $tags;
    }
}
