<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Seo\JsonLd\Graph;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function preg_match;
use function strlen;
use function substr;

#[Group('unit')]
final class GraphTest extends TestCase
{
    #[Test]
    public function keepsTheNodesInTheOrderTheyWereAppendedLeavingOutEmptyOnes(): void
    {
        $graph = (new Graph())->append([], ['@type' => 'WebSite'])
            ->append(['@type' => 'Article']);

        static::assertSame([['@type' => 'WebSite'], ['@type' => 'Article']], $graph->nodes());
    }

    #[Test]
    public function writesEveryNodeInOneGraphThatItsValuesCannotClose(): void
    {
        $headline = "</script><b>Tom's & \"Jerry's\"</b>";
        $script   = (string) (new Graph())->append(['@type' => 'Article', 'headline' => $headline]);
        $json     = substr($script, strlen('<script type="application/ld+json">'), -strlen('</script>'));

        static::assertSame(
            [
                '<script type="application/ld+json">',
                '</script>',
                0,
                ['@context' => 'https://schema.org', '@graph' => [['@type' => 'Article', 'headline' => $headline]]],
            ],
            [
                substr($script, offset: 0, length: strlen('<script type="application/ld+json">')),
                substr($script, -strlen('</script>')),
                preg_match('/[<>&\']/', $json),
                json_decode($json, associative: true),
            ],
        );
    }

    #[Test]
    public function writesNoNonceWhenItIsEmpty(): void
    {
        static::assertSame(
            '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"Thing"}]}</script>',
            (new Graph())->append(['@type' => 'Thing'])->script(''),
        );
    }

    #[Test]
    public function writesNoScriptWithoutNodes(): void
    {
        $graph = (new Graph())->append([]);

        static::assertSame(['', ''], [$graph->script('abc'), (string) $graph]);
    }

    #[Test]
    public function writesTheNonceEscaped(): void
    {
        static::assertSame(
            '<script type="application/ld+json" nonce="a&quot;b">{"@context":"https://schema.org","@graph":[{"@type":"Thing"}]}</script>',
            (new Graph())->append(['@type' => 'Thing'])->script('a"b'),
        );
    }
}
