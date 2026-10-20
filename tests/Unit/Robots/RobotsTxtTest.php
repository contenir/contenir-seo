<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Robots;

use Contenir\Seo\Robots\RobotsRules;
use Contenir\Seo\Robots\RobotsTxt;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RobotsTxtTest extends TestCase
{
    #[Test]
    public function allowsEverythingAndNamesTheSitemap(): void
    {
        $robots = new RobotsTxt('https://www.example.com', sitemap: 'https://www.example.com/sitemap.xml');

        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nAllow: /\n\n"
                . "Sitemap: https://www.example.com/sitemap.xml\n",
            $robots->render(),
        );
    }

    #[Test]
    public function appendsTheSitesRulesBeforeTheSitemap(): void
    {
        $robots = new RobotsTxt(
            'https://www.example.com',
            sitemap: 'https://www.example.com/sitemap.xml',
            rules: RobotsRules::parse("Disallow: /search\n\nUser-agent: GPTBot\nDisallow: /"),
        );

        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nAllow: /\n\nDisallow: /search\n\n"
                . "User-agent: GPTBot\nDisallow: /\n\nSitemap: https://www.example.com/sitemap.xml\n",
            $robots->render(),
        );
    }

    #[Test]
    public function disallowsEverythingOnAHiddenCopyWithoutRulesOrSitemap(): void
    {
        $robots = new RobotsTxt(
            'https://staging.example.com',
            hidden: true,
            sitemap: 'https://staging.example.com/sitemap.xml',
            rules: RobotsRules::parse('Disallow: /search'),
        );

        static::assertSame(
            "# robots.txt for https://staging.example.com/\n\n# This copy of the site is hidden from search engines.\n"
                . "User-agent: *\nDisallow: /\n",
            $robots->render(),
        );
    }

    #[Test]
    public function namesNoSitemapWhenTheSiteHasNone(): void
    {
        static::assertSame(
            "# robots.txt for https://www.example.com/\n\nUser-agent: *\nAllow: /\n",
            (new RobotsTxt('https://www.example.com'))->render(),
        );
    }
}
