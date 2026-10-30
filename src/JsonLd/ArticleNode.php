<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Seo\Head\Article;
use Contenir\Seo\Organisation\Organisation;

use const DATE_ATOM;

/**
 * An article, project or news story as JSON-LD, published by the site's owner.
 *
 * @internal
 */
final class ArticleNode
{
    /**
     * @param string $home     The home page's address, with its trailing slash
     * @param string $siteName The name to fall back on for the publisher
     *
     * @return array<string, mixed>
     */
    public static function of(Article $article, Organisation $organisation, string $home, string $siteName): array
    {
        $name = $organisation->business->name ?? '';

        return Node::of([
            '@type'         => $article->type,
            'headline'      => $article->headline,
            'url'           => $article->url,
            'description'   => $article->description,
            'image'         => $article->image,
            'datePublished' => $article->published?->format(DATE_ATOM),
            'dateModified'  => $article->modified?->format(DATE_ATOM),
            'publisher'     => Node::of([
                '@type' => 'Organization',
                '@id'   => "{$home}#organization",
                'name'  => '' === $name ? $siteName : $name,
                'url'   => $home,
                'logo'  => $organisation->logo,
            ]),
        ]);
    }
}
