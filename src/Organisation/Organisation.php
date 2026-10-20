<?php

declare(strict_types=1);

namespace Contenir\Seo\Organisation;

/**
 * Who owns the site, as JSON-LD describes it on the home page.
 *
 * @api
 */
final readonly class Organisation
{
    /**
     * @param ?string      $logo    The logo's absolute URL
     * @param list<string> $profiles Profile URLs on social networks, for sameAs
     */
    public function __construct(
        public string $name = '',
        public OrganisationType $type = OrganisationType::Organization,
        public ?string $logo = null,
        public array $profiles = [],
    ) {}
}
