<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Seo\Head\Article;
use Contenir\Seo\Organisation\Organisation;

use function in_array;

use const DATE_ATOM;

/**
 * A page's main JSON-LD node, of the schema.org type its Article names. An
 * Article, NewsArticle or BlogPosting is described by its headline and the
 * dates it was published and modified; any other type, such as WebPage or
 * CreativeWork, by its name, the dates it was created and modified, and the
 * site it is part of. The site's owner publishes either.
 */
final class ArticleNode
{
    /** The types described as articles. */
    public const array ARTICLES = ['Article', 'NewsArticle', 'BlogPosting'];

    /**
     * @param string $home     The home page's address, with its trailing slash
     * @param string $siteName The name to fall back on for the publisher
     *
     * @return array<string, mixed>
     */
    public static function of(Article $article, Organisation $organisation, string $home, string $siteName): array
    {
        $name      = $organisation->business->name ?? '';
        $publisher = Node::of([
            '@type' => 'Organization',
            '@id'   => "{$home}#organization",
            'name'  => '' === $name ? $siteName : $name,
            'url'   => $home,
            'logo'  => $organisation->logo,
        ]);

        if (in_array($article->type, self::ARTICLES, strict: true)) {
            return Node::of([
                '@type'         => $article->type,
                'headline'      => $article->headline,
                'url'           => $article->url,
                'description'   => $article->description,
                'image'         => $article->image,
                'datePublished' => $article->published?->format(DATE_ATOM),
                'dateModified'  => $article->modified?->format(DATE_ATOM),
                'publisher'     => $publisher,
            ]);
        }

        return Node::of([
            '@type'        => $article->type,
            'name'         => $article->headline,
            'url'          => $article->url,
            'description'  => $article->description,
            'image'        => $article->image,
            'dateCreated'  => $article->published?->format(DATE_ATOM),
            'dateModified' => $article->modified?->format(DATE_ATOM),
            'isPartOf'     => ['@id' => "{$home}#website"],
            'publisher'    => $publisher,
        ]);
    }
}
