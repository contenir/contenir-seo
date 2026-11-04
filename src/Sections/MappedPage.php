<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

/**
 * What a record's <seo> block makes of its row: each role's first value
 * that isn't empty, and the per-record overrides its advanced column holds.
 * Every value is optional.
 *
 * @api
 */
final readonly class MappedPage
{
    /**
     * @param string|null $canonical An absolute URL, or a path on the site
     * @param string|null $robots    The robots meta tag's content, such as "noindex, follow"
     * @param string|null $schema    The page's main schema.org type
     *
     * @mago-expect lint:excessive-parameter-list One parameter per role a block maps; all are optional.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $schema = null,
    ) {}
}
