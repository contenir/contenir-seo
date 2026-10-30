<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

use DateTimeInterface;

/**
 * A page that is an article, a project or a news story, as its JSON-LD describes it.
 *
 * @api
 */
final readonly class Article
{
    /**
     * @param string $url      The page's canonical address
     * @param string $headline The page's own title
     * @param string $type     The schema.org type: Article, NewsArticle or BlogPosting
     *
     * @mago-expect lint:excessive-parameter-list One parameter per property the article's JSON-LD takes.
     */
    public function __construct(
        public string $url,
        public string $headline,
        public ?string $description = null,
        public ?string $image = null,
        public ?DateTimeInterface $published = null,
        public ?DateTimeInterface $modified = null,
        public string $type = 'Article',
    ) {}
}
