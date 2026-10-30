<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Head;

use Contenir\Seo\Head\Head;
use Contenir\Seo\Head\HtmlRenderer;
use Contenir\Seo\Head\MetaTag;
use Contenir\Seo\JsonLd\JsonLd;
use Contenir\Seo\Organisation\Organisation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class HtmlRendererTest extends TestCase
{
    #[Test]
    public function escapesApostrophesAndInvalidText(): void
    {
        $head = new Head("It's", [MetaTag::name('description', "Bad \xC3 byte")]);

        static::assertSame(
            "<title>It&apos;s</title>\n<meta name=\"description\" content=\"Bad \u{FFFD} byte\">",
            (new HtmlRenderer())->render($head),
        );
    }

    #[Test]
    public function writesNoScriptWhenTheGraphIsEmpty(): void
    {
        $head = new Head('Home', jsonLd: (new JsonLd('', 'Example', new Organisation()))->nodes());

        static::assertSame('<title>Home</title>', (new HtmlRenderer())->render($head));
    }

    #[Test]
    public function writesNothingForAnEmptyHead(): void
    {
        $head = new Head('');

        static::assertSame(['', [], [], false], [
            (new HtmlRenderer())->render($head),
            $head->tags,
            $head->jsonLd,
            $head->hidden,
        ]);
    }

    #[Test]
    public function writesTheTagsWithoutTheScriptForATemplateThatGathersItsOwnGraph(): void
    {
        $head = new Head(
            'About',
            [MetaTag::property('og:site_name', 'Example')],
            (new JsonLd('https://www.example.com', 'Example', new Organisation()))->nodes(),
        );

        static::assertSame(
            "<title>About</title>\n<meta property=\"og:site_name\" content=\"Example\">",
            (new HtmlRenderer())->tags($head),
        );
    }

    #[Test]
    public function writesTheTitleTagsAndScriptEscaped(): void
    {
        $head = new Head(
            'Fish & "Chips"',
            [MetaTag::property('og:site_name', 'Fish & Chips'), MetaTag::name('robots', '<noindex>')],
            (new JsonLd('https://www.example.com', 'Example', new Organisation()))->nodes(),
        );

        static::assertSame(
            "<title>Fish &amp; &quot;Chips&quot;</title>\n"
                . "<meta property=\"og:site_name\" content=\"Fish &amp; Chips\">\n"
                . "<meta name=\"robots\" content=\"&lt;noindex&gt;\">\n"
                . (new JsonLd('https://www.example.com', 'Example', new Organisation()))->script('abc'),
            (new HtmlRenderer())->render($head, 'abc'),
        );
    }
}
