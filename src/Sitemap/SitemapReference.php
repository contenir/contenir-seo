<?php

declare(strict_types=1);

namespace Contenir\Seo\Sitemap;

use DateTimeInterface;

/**
 * One <sitemap> of a sitemap index: where one page of a large sitemap is,
 * and when its newest URL last changed.
 *
 * @api
 */
final readonly class SitemapReference
{
    public function __construct(
        public string $location,
        public ?DateTimeInterface $modified = null,
    ) {}
}
