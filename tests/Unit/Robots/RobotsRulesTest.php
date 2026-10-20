<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Robots;

use Contenir\Seo\Robots\RobotsRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RobotsRulesTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function notRules(): array
    {
        return [
            'no colon'                => ['Disallow /search'],
            'unknown directive'       => ['Noindex: /search'],
            'a value that is no path' => ['Disallow: search'],
            'an Allow without a path' => ['Allow: everything'],
            'a control character'     => ["Disallow: /a\x07b"],
            'a bare word'             => ['nonsense'],
            'a directive alone'       => ['Disallow'],
        ];
    }

    #[Test]
    public function hasNoProblemsWithRules(): void
    {
        static::assertSame(
            [],
            RobotsRules::problems("User-agent: GPTBot\r\nDisallow: /\r\n\r\n# Search\rDisallow: /search"),
        );
    }

    #[Test]
    public function hasNoRulesByDefault(): void
    {
        $rules = new RobotsRules();

        static::assertSame([[], ''], [$rules->lines, $rules->text()]);
    }

    #[DataProvider('notRules')]
    #[Test]
    public function leavesOutAndReportsLinesThatAreNotRules(string $line): void
    {
        static::assertSame(
            [
                [],
                [
                    'Line 2 isn\'t a robots.txt rule. Start each line with User-agent:, Allow:, Disallow:, Crawl-delay:'
                        . ' or Sitemap:, or with # for a comment.',
                ],
            ],
            [RobotsRules::parse($line)->lines, RobotsRules::problems("# kept\n{$line}")],
        );
    }

    #[Test]
    public function readsWindowsAndOldMacLineEndings(): void
    {
        static::assertSame(
            ['Disallow: /a', 'Disallow: /b', 'Disallow: /c'],
            RobotsRules::parse("Disallow: /a\r\nDisallow: /b\rDisallow: /c")->lines,
        );
    }

    #[Test]
    public function tidiesSpacingAndDropsLinesThatAreNotRulesWithoutAGap(): void
    {
        static::assertSame(
            ['# Keep search out', 'Disallow: /search', 'Disallow: /cart'],
            RobotsRules::parse("   # Keep search out\n  Disallow : /search  \nnot a rule\nDisallow: /cart")->lines,
        );
    }

    #[Test]
    public function writesEachDirectiveAsRobotsTxtSpellsIt(): void
    {
        $rules = RobotsRules::parse(
            "\n\n  user-agent:GPTBot  \nDISALLOW: /\n\n# Comments stay\nallow: *.css\ndisallow:\ncrawl-delay: 10\n"
                . "SITEMAP: https://www.example.com/news-sitemap.xml\n\n\n",
        );

        static::assertSame(
            "User-agent: GPTBot\nDisallow: /\n\n# Comments stay\nAllow: *.css\nDisallow:\nCrawl-delay: 10\n"
                . 'Sitemap: https://www.example.com/news-sitemap.xml',
            $rules->text(),
        );
    }
}
