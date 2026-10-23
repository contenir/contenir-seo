<?php

declare(strict_types=1);

namespace Contenir\Seo\Organisation;

use Contenir\Contact\Business;

/**
 * Who owns the site, as JSON-LD describes it on the home page: the business
 * and its offices from Office details, with the logo and social profiles the
 * SEO and Social add-ons keep.
 *
 * @api
 */
final readonly class Organisation
{
    /**
     * @param ?Business    $business The business and its offices, or null for a site without them
     * @param ?string      $logo     The logo's absolute URL
     * @param list<string> $profiles Profile URLs on social networks, for sameAs
     */
    public function __construct(
        public ?Business $business = null,
        public ?string $logo = null,
        public array $profiles = [],
    ) {}
}
