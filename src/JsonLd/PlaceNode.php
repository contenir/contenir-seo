<?php

declare(strict_types=1);

namespace Contenir\Seo\JsonLd;

use Contenir\Contact\Office;

use function rawurlencode;

/**
 * One office as a Place the organisation is at, with an @id a page about the
 * office can point to.
 *
 * @internal
 */
final class PlaceNode
{
    /**
     * @return array<string, mixed>
     */
    public static function of(string $home, Office $office): array
    {
        return Node::of([
            '@type'                     => 'Place',
            '@id'                       => self::id($home, $office),
            'name'                      => $office->name,
            'address'                   => $office->address?->schema(),
            'telephone'                 => $office->telephone?->international(),
            'geo'                       => $office->geolocation?->schema(),
            'openingHoursSpecification' => $office->hour->specifications(),
        ]);
    }

    /**
     * The office's @id on the site: "https://www.example.com/#office-k3f9q2".
     *
     * @param string $home The home page's address, with its trailing slash
     */
    private static function id(string $home, Office $office): string
    {
        return "{$home}#office-" . rawurlencode($office->id);
    }
}
