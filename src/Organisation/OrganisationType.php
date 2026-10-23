<?php

declare(strict_types=1);

namespace Contenir\Seo\Organisation;

/**
 * The schema.org type the site's owner was described as.
 *
 * @deprecated The business type is Office details' now, read through contenir/contenir-contact's BusinessType;
 *             this is kept for the SEO add-on's settings form until it drops the field.
 *
 * @api
 */
enum OrganisationType: string
{
    case Organization = 'Organization';

    case LocalBusiness = 'LocalBusiness';

    /**
     * Labels for a settings field, by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Organization->value  => 'Organisation',
            self::LocalBusiness->value => 'Local business',
        ];
    }
}
