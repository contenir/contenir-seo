<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

/**
 * A record's own values for its page, from its advanced column: a share
 * title, description, image or alt text an editor set for that record
 * alone. Each wins over what the page would otherwise show.
 *
 * @api
 */
final readonly class RecordOverrides
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
    ) {}
}
