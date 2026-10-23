<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Contact\Business;
use Contenir\Contact\Office;
use Contenir\Contact\Type\AdditionalType;
use Contenir\Contact\Type\BusinessType;
use Contenir\Seo\Organisation\Organisation;

use function array_filter;
use function array_map;
use function count;

use const ARRAY_FILTER_USE_KEY;

/**
 * The site's owner as JSON-LD: the business, at its primary office.
 *
 * The primary office's address, position, telephone, email, opening hours
 * and type-specific values are the business's own, as far as the business's
 * type takes them. With more than one office shown to search engines, each
 * of them, the primary office included, is also listed as a location.
 *
 * @internal
 */
final class OrganisationNode
{
    /**
     * @param string $home     The home page's address, with its trailing slash
     * @param string $siteName The name to fall back on
     *
     * @return array<string, mixed>
     */
    public static function of(Organisation $organisation, string $home, string $siteName): array
    {
        $business = $organisation->business;
        $type     = $business->type ?? BusinessType::from(null);
        $name     = $business->name ?? '';

        return Node::of([
            '@type'          => $type->name,
            '@id'            => "{$home}#organization",
            'additionalType' => self::additionalTypes($business),
            'name'           => '' === $name ? $siteName : $name,
            'legalName'      => $business?->legalName,
            'taxID'          => $business?->taxId?->number(),
            'url'            => $home,
            'logo'           => $organisation->logo,
            'sameAs'         => $organisation->profiles,
            ...(null === $business ? [] : self::located($business, $type, $home)),
        ]);
    }

    /**
     * @return string|list<string>
     */
    private static function additionalTypes(?Business $business): string|array
    {
        $urls = array_map(static fn(AdditionalType $type): string => $type->url(), $business->additionalTypes ?? []);

        return 1 === count($urls) ? $urls[0] : $urls;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function contactPoint(Office|Business $at): ?array
    {
        $contact = Node::of(['telephone' => $at->telephone?->international(), 'email' => $at->email?->address]);

        return [] === $contact ? null : ['@type' => 'ContactPoint', 'contactType' => 'customer service', ...$contact];
    }

    /**
     * The values the business has at its primary office, or of its own when
     * it has no offices.
     *
     * @return array<string, mixed>
     */
    private static function located(Business $business, BusinessType $type, string $home): array
    {
        $primary = $business->offices->primary();
        $at      = $primary ?? $business;
        $offices = $business->offices->searchable()->all();

        return [
            'address'                   => $primary?->address?->schema(),
            'telephone'                 => $at->telephone?->international(),
            'email'                     => $at->email?->address,
            'contactPoint'              => self::contactPoint($at),
            'geo'                       => $type->takes('geo') ? $primary?->geolocation?->schema() : null,
            'openingHoursSpecification' => $type->takes('openingHoursSpecification')
                ? $at->hour->specifications()
                : null,
            ...array_filter($at->detail->schema(), $type->takes(...), ARRAY_FILTER_USE_KEY),
            'location' => count($offices) > 1 ? self::locations($home, $offices) : null,
        ];
    }

    /**
     * @param list<Office> $offices
     *
     * @return list<array<string, mixed>>
     */
    private static function locations(string $home, array $offices): array
    {
        $places = [];
        foreach ($offices as $office) {
            $places[] = PlaceNode::of($home, $office);
        }

        return $places;
    }
}
