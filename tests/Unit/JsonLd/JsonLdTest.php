<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Seo\JsonLd\JsonLd;
use Contenir\Seo\Organisation\Organisation;
use Contenir\Seo\Organisation\OrganisationType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_values;
use function json_decode;
use function str_contains;
use function strlen;
use function substr;
use function substr_count;

#[Group('unit')]
final class JsonLdTest extends TestCase
{
    #[Test]
    public function describesTheOwnerAndTheSiteInOneGraph(): void
    {
        $jsonLd = new JsonLd(
            'https://www.example.com',
            'Example',
            new Organisation(
                'Example Pty Ltd',
                OrganisationType::LocalBusiness,
                'https://www.example.com/logo.png',
                ['https://www.facebook.com/example', 'https://x.com/example'],
            ),
        );

        static::assertSame(
            [
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type'  => 'LocalBusiness',
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
        $jsonLd = new JsonLd('', 'Example', new Organisation('Example'));

        static::assertSame([[], ''], [$jsonLd->graph(), $jsonLd->script('n0nce')]);
    }

    #[Test]
    public function namesTheOwnerAfterTheSiteWithoutLogoOrProfiles(): void
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
}
