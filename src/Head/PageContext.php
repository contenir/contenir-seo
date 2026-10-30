<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

/**
 * What the head builder needs to know about the page being rendered: its
 * own title, whether it is the home page, the host it was requested on,
 * whether it already has a description and a share image of its own, its
 * path, for its breadcrumb trail, and when it is an article, the article.
 *
 * @api
 */
final readonly class PageContext
{
    /**
     * @mago-expect lint:excessive-parameter-list One parameter per fact about the page, given by name.
     */
    public function __construct(
        public ?string $title = null,
        public bool $home = false,
        public string $host = '',
        public bool $hasDescription = false,
        public bool $hasImage = false,
        public string $path = '',
        public ?Article $article = null,
    ) {}
}
