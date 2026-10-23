<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Contact\Business;
use Contenir\Contact\ContactSettings;
use Contenir\Seo\JsonLd\JsonLd;
use Contenir\Seo\Organisation\Organisation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_key_exists;
use function array_values;
use function count;
use function json_decode;
use function str_contains;
use function strlen;
use function substr;
use function substr_count;

#[Group('unit')]
final class JsonLdTest extends TestCase
{
    private const array MELBOURNE = [
        'id'          => 'k3f9q2',
        'name'        => 'Melbourne studio',
        'street'      => '402 Queens Parade',
        'locality'    => 'Fitzroy North',
        'region'      => 'VIC',
        'postcode'    => '3068',
        'geolocation' => ['latitude' => -37.7787, 'longitude' => 144.9874],
    ];

    private const array HOURS = [['day' => ['Mo', 'Tu', 'We', 'Th', 'Fr'], 'opens' => '09:00', 'closes' => '17:30']];

    /**
     * @param array<string, mixed>       $business
     * @param list<array<string, mixed>> $offices
     */
    private static function business(array $business = [], array $offices = []): Business
    {
        return ContactSettings::fromSettings([
            'site'    => ['title' => 'Studio Esteta', 'locale' => 'en_AU', 'timezone' => 'Australia/Melbourne'],
            'contact' => ['business' => $business, 'office' => $offices],
        ]);
    }

    /**
     * The organisation node of the home page's graph.
     *
     * @return array<array-key, mixed>
     */
    private static function node(Business $business, ?string $logo = null, array $profiles = []): array
    {
        $graph = (new JsonLd(
            'https://www.example.com',
            'Example',
            new Organisation($business, $logo, $profiles),
        ))->graph();

        return $graph['@graph'][0] ?? [];
    }

    #[Test]
    public function aBusinessWithoutANameIsNamedAfterTheSite(): void
    {
        $business = ContactSettings::fromSettings([]);

        static::assertSame('Example', self::node($business)['name'] ?? null);
    }

    #[Test]
    public function aBusinessWithoutOfficesHasItsOwnHoursButNoAddress(): void
    {
        $node = self::node(self::business(['type' => 'LocalBusiness', 'hour' => self::HOURS]));

        static::assertSame([false, false, 1], [
            array_key_exists('address', $node),
            array_key_exists('geo', $node),
            count($node['openingHoursSpecification'] ?? []),
        ]);
    }

    #[Test]
    public function aLocalBusinessWithOneOfficeIsAtThatOffice(): void
    {
        static::assertSame(
            [
                '@type'                     => 'LocalBusiness',
                '@id'                       => 'https://www.example.com/#organization',
                'additionalType'            => 'https://www.wikidata.org/wiki/Q4387609',
                'name'                      => 'Studio Esteta',
                'legalName'                 => 'Studio Esteta Pty Ltd',
                'taxID'                     => '12 345 678 901',
                'url'                       => 'https://www.example.com/',
                'address'                   => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => '402 Queens Parade',
                    'addressLocality' => 'Fitzroy North',
                    'addressRegion'   => 'VIC',
                    'postalCode'      => '3068',
                    'addressCountry'  => 'AU',
                ],
                'telephone'                 => '+61 3 9428 0228',
                'email'                     => 'studio@example.test',
                'contactPoint'              => [
                    '@type'       => 'ContactPoint',
                    'contactType' => 'customer service',
                    'telephone'   => '+61 3 9428 0228',
                    'email'       => 'studio@example.test',
                ],
                'geo'                       => [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => -37.7787,
                    'longitude' => 144.9874,
                ],
                'openingHoursSpecification' => [
                    [
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                        'opens'     => '09:00',
                        'closes'    => '17:30',
                    ],
                ],
            ],
            self::node(self::business(
                [
                    'legal_name'      => 'Studio Esteta Pty Ltd',
                    'tax_id'          => '12345678901',
                    'type'            => 'LocalBusiness',
                    'additional_type' => ['Q4387609'],
                    'telephone'       => '03 9428 0228',
                    'email'           => 'studio@example.test',
                    'hour'            => self::HOURS,
                ],
                [self::MELBOURNE],
            )),
        );
    }

    #[Test]
    public function aPlainOrganizationHasNoPositionOrHours(): void
    {
        $node = self::node(self::business(['hour' => self::HOURS], [self::MELBOURNE]));

        static::assertSame(
            ['Organization', true, false, false],
            [
                $node['@type'] ?? null,
                array_key_exists('address', $node),
                array_key_exists('geo', $node),
                array_key_exists('openingHoursSpecification', $node),
            ],
        );
    }

    #[Test]
    public function describesTheOwnerAndTheSiteInOneGraph(): void
    {
        $jsonLd = new JsonLd(
            'https://www.example.com',
            'Example',
            new Organisation(
                self::business(['name' => 'Example Pty Ltd']),
                'https://www.example.com/logo.png',
                ['https://www.facebook.com/example', 'https://x.com/example'],
            ),
        );

        static::assertSame(
            [
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type'  => 'Organization',
                        '@id'    => 'https://www.example.com/#organization',
                        'name'   => 'Example Pty Ltd',
                        'url'    => 'https://www.example.com/',
                        'logo'   => 'https://www.example.com/logo.png',
                        'sameAs' => ['https://www.facebook.com/example', 'https://x.com/example'],
                    ],
                    [
                        '@type'     => 'WebSite',
                        '@id'       => 'https://www.example.com/#website',
                        'url'       => 'https://www.example.com/',
                        'name'      => 'Example',
                        'publisher' => ['@id' => 'https://www.example.com/#organization'],
                    ],
                ],
            ],
            $jsonLd->graph(),
        );
    }

    #[Test]
    public function escapesTheNonce(): void
    {
        $script = (new JsonLd('https://www.example.com', 'Example', new Organisation()))->script("it's\xC3");

        static::assertStringStartsWith("<script type=\"application/ld+json\" nonce=\"it&apos;s\u{FFFD}\">", $script);
    }

    #[Test]
    public function hasNoGraphWithoutASiteUrl(): void
    {
        $jsonLd = new JsonLd('', 'Example', new Organisation());

        static::assertSame([[], ''], [$jsonLd->graph(), $jsonLd->script('n0nce')]);
    }

    #[Test]
    public function keepsOnlyTheTypeSpecificValuesTheBusinessesTypeTakes(): void
    {
        $node = self::node(self::business(
            ['type' => 'Dentist', 'price_range' => '$$'],
            [[...self::MELBOURNE, 'type' => 'Bakery', 'cuisine' => ['Italian']]],
        ));

        static::assertSame(['$$', false], [$node['priceRange'] ?? null, array_key_exists('servesCuisine', $node)]);
    }

    #[Test]
    public function listsEveryOfficeShownToSearchEnginesAsALocation(): void
    {
        $node = self::node(self::business([
            'type'      => 'LocalBusiness',
            'telephone' => '03 9428 0228',
            'hour'      => self::HOURS,
        ], [
            self::MELBOURNE,
            ['id' => 'warehouse', 'name' => 'Warehouse', 'locality' => 'Brunswick', 'search' => false],
            [
                'id'       => 'p/8',
                'name'     => 'Perth',
                'street'   => '1 Hay St',
                'locality' => 'Perth',
                'region'   => 'WA',
                'hour'     => [],
            ],
        ]));

        static::assertSame(
            [
                [
                    '@type'                     => 'Place',
                    '@id'                       => 'https://www.example.com/#office-k3f9q2',
                    'name'                      => 'Melbourne studio',
                    'address'                   => [
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => '402 Queens Parade',
                        'addressLocality' => 'Fitzroy North',
                        'addressRegion'   => 'VIC',
                        'postalCode'      => '3068',
                        'addressCountry'  => 'AU',
                    ],
                    'telephone'                 => '+61 3 9428 0228',
                    'geo'                       => [
                        '@type'     => 'GeoCoordinates',
                        'latitude'  => -37.7787,
                        'longitude' => 144.9874,
                    ],
                    'openingHoursSpecification' => [
                        [
                            '@type'     => 'OpeningHoursSpecification',
                            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                            'opens'     => '09:00',
                            'closes'    => '17:30',
                        ],
                    ],
                ],
                [
                    '@type'     => 'Place',
                    '@id'       => 'https://www.example.com/#office-p%2F8',
                    'name'      => 'Perth',
                    'address'   => [
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => '1 Hay St',
                        'addressLocality' => 'Perth',
                        'addressRegion'   => 'WA',
                        'addressCountry'  => 'AU',
                    ],
                    'telephone' => '+61 3 9428 0228',
                ],
            ],
            $node['location'] ?? null,
        );
    }

    #[Test]
    public function oneOfficeShownToSearchEnginesIsNoList(): void
    {
        $node = self::node(self::business([], [
            self::MELBOURNE,
            ['id' => 'warehouse', 'name' => 'Warehouse', 'locality' => 'Brunswick', 'search' => false],
        ]));

        static::assertFalse(array_key_exists('location', $node));
    }

    #[Test]
    public function thePrimaryOfficesOwnValuesAreTheBusinesses(): void
    {
        $node = self::node(self::business(
            ['type' => 'LocalBusiness', 'telephone' => '03 9428 0228', 'email' => 'studio@example.test'],
            [[...self::MELBOURNE, 'telephone' => '03 9000 0000', 'email' => null]],
        ));

        static::assertSame(
            [
                '+61 3 9000 0000',
                null,
                ['@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => '+61 3 9000 0000'],
            ],
            [$node['telephone'] ?? null, $node['email'] ?? null, $node['contactPoint'] ?? null],
        );
    }

    #[Test]
    public function withoutABusinessTheOwnerIsAnOrganizationNamedAfterTheSite(): void
    {
        $graph = (new JsonLd('https://www.example.com', 'Example', new Organisation()))->graph();

        static::assertSame(
            [
                '@type' => 'Organization',
                '@id'   => 'https://www.example.com/#organization',
                'name'  => 'Example',
                'url'   => 'https://www.example.com/',
            ],
            $graph['@graph'][0] ?? null,
        );
    }

    #[Test]
    public function writesAScriptThatCannotBeClosedByItsValues(): void
    {
        $name   = '</script><script>alert("x")</script> & \'café\'';
        $script = (new JsonLd('https://www.example.com', $name, new Organisation()))->script('a"b');
        $open   = '<script type="application/ld+json" nonce="a&quot;b">';
        $json   = substr($script, strlen($open), -strlen('</script>'));

        static::assertSame(
            [$open, 1, [], $name, 'café'],
            [
                substr($script, offset: 0, length: strlen($open)),
                substr_count($script, needle: '</script'),
                array_values(array_filter(
                    ['<', '>', '&', '"name":"\'', ' \'café'],
                    static fn(string $raw): bool => str_contains($json, $raw),
                )),
                json_decode($json, associative: true)['@graph'][1]['name'] ?? null,
                str_contains($json, 'café') ? 'café' : 'escaped',
            ],
        );
    }

    #[Test]
    public function writesAScriptWithoutANonceWhenThereIsNone(): void
    {
        $script = (new JsonLd('https://www.example.com', "Bad \xC3", new Organisation()))->script('');

        static::assertStringStartsWith('<script type="application/ld+json">{"@context"', $script);
        static::assertStringContainsString("\"name\":\"Bad \u{FFFD}\"", $script);
    }

    #[Test]
    public function writesSeveralAdditionalTypesAsAList(): void
    {
        static::assertSame(
            ['https://www.wikidata.org/wiki/Q4387609', 'https://www.wikidata.org/wiki/Q123024699'],
            self::node(self::business(['additional_type' => ['Q4387609', 'Q123024699']]))['additionalType'] ?? null,
        );
    }
}
