<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

/**
 * What the head builder needs to know about the page being rendered: its
 * own title, whether it is the home page, the host it was requested on,
 * and whether it already has a description and a share image of its own.
 *
 * @api
 */
final readonly class PageContext
{
    public function __construct(
        public ?string $title = null,
        public bool $home = false,
        public string $host = '',
        public bool $hasDescription = false,
        public bool $hasImage = false,
    ) {}
}
