<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit;

use Contenir\Seo\Organisation\OrganisationType;
use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\SeoConfig;
use Contenir\Seo\SettingKeys;
use Contenir\Seo\Tests\TestAsset\Factory\SettingsFactory;
use Contenir\Seo\Title\TitlePosition;
use Contenir\Seo\Visibility\Visibility;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SeoConfigTest extends TestCase
{
    #[Test]
    public function fallsBackToDefaultsForASiteWithNoSeoSettings(): void
    {
        $config = SeoConfig::fromSettings(SettingsFactory::site());

        static::assertSame(
            [
                'https://www.example.com',
                'Example',
                '|',
                TitlePosition::After,
                '',
                '',
                null,
                RobotsDirective::IndexFollow,
                Visibility::Automatic,
                [],
                '',
                OrganisationType::Organization,
                null,
                [],
                true,
                [],
                [],
                [],
            ],
            [
                $config->site->origin,
                $config->siteName(),
                $config->title->separator,
                $config->title->position,
                $config->title->homeTitle,
                $config->description,
                $config->shareImage,
                $config->robots,
                $config->visibility,
                $config->verification,
                $config->organisation->name,
                $config->organisation->type,
                $config->organisation->logo,
                $config->organisation->profiles,
                $config->sitemap->enabled,
                $config->sitemap->excluded,
                $config->robotsRules->lines,
                $config->problems,
            ],
        );
    }

    #[Test]
    public function hasNothingByDefault(): void
    {
        $config = new SeoConfig();

        static::assertSame(
            ['', '', Visibility::Show, true, []],
            [
                $config->site->origin,
                $config->siteName(),
                $config->visibility,
                $config->sitemap->enabled,
                $config->problems,
            ],
        );
    }

    #[Test]
    public function keepsOnlyTheCoreSettingsWhenTheAddOnIsNotEnabled(): void
    {
        $config = SeoConfig::fromCoreSettings(SettingsFactory::site([
            SettingKeys::SITEMAP_ENABLED => '0',
            SettingKeys::VISIBILITY      => 'hide',
            SettingKeys::VERIFY_GOOGLE   => 'token',
            SettingKeys::SITE_NAME       => 'Other',
        ]));

        static::assertSame(
            ['https://www.example.com', 'Example', TitlePosition::None, false, Visibility::Show, []],
            [
                $config->site->origin,
                $config->siteName(),
                $config->title->position,
                $config->sitemap->enabled,
                $config->visibility,
                $config->verification,
            ],
        );
    }

    #[Test]
    public function keepsTheProblemWithEachBadValueAndLeavesItOut(): void
    {
        $config = SeoConfig::fromSettings(SettingsFactory::site([
            SettingKeys::VERIFY_BING   => 'not a token',
            SettingKeys::VERIFY_GOOGLE => 'fine',
            SettingKeys::ROBOTS_RULES  => "Disallow: /search\nNoindex: /",
        ]));

        static::assertSame(
            [
                [
                    SettingKeys::VERIFY_BING  => 'This doesn\'t look like a Bing Webmaster Tools verification token.'
                        . ' Paste the content value of the meta tag Bing Webmaster Tools gives you, or the whole tag.',
                    SettingKeys::ROBOTS_RULES => 'Line 2 isn\'t a robots.txt rule. Start each line with User-agent:,'
                        . ' Allow:, Disallow:, Crawl-delay: or Sitemap:, or with # for a comment.',
                ],
                ['google' => 'fine'],
                ['Disallow: /search'],
            ],
            [$config->problems, $config->verification, $config->robotsRules->lines],
        );
    }

    #[Test]
    public function keepsTheSitemapOnWhenTheCoreSettingsDoNotSay(): void
    {
        static::assertTrue(SeoConfig::fromCoreSettings([])->sitemap->enabled);
    }

    #[Test]
    public function keepsTheTokensAfterABadOne(): void
    {
        $config = SeoConfig::fromSettings(SettingsFactory::site([
            SettingKeys::VERIFY_BING => 'not a token',
            SettingKeys::VERIFY_META => 'm-token',
        ]));

        static::assertSame(['meta' => 'm-token'], $config->verification);
    }

    #[Test]
    public function readsAPaddedSiteTitle(): void
    {
        static::assertSame(
            'Example',
            SeoConfig::fromSettings(SettingsFactory::site([SettingKeys::SITE_TITLE => ' Example ']))->siteName(),
        );
    }

    #[Test]
    public function readsEverySetting(): void
    {
        $config = SeoConfig::fromSettings(SettingsFactory::site([
            SettingKeys::SITE_NAME         => ' Example Studio ',
            SettingKeys::TITLE_SEPARATOR   => ' – ',
            SettingKeys::TITLE_POSITION    => 'before',
            SettingKeys::HOME_TITLE        => ' Handmade furniture ',
            SettingKeys::DESCRIPTION       => ' We make furniture. ',
            SettingKeys::SHARE_IMAGE       => '/asset/img/seo/share.jpg',
            SettingKeys::SHARE_IMAGE_ALT   => ' The workshop ',
            SettingKeys::ROBOTS            => 'noindex, follow',
            SettingKeys::VISIBILITY        => 'show',
            SettingKeys::VERIFY_GOOGLE     => '<meta name="google-site-verification" content="g-token">',
            SettingKeys::VERIFY_BING       => 'b-token',
            SettingKeys::VERIFY_PINTEREST  => 'p-token',
            SettingKeys::VERIFY_META       => 'm-token',
            SettingKeys::ORGANISATION_NAME => ' Example Pty Ltd ',
            SettingKeys::ORGANISATION_TYPE => 'LocalBusiness',
            SettingKeys::ORGANISATION_LOGO => '/asset/img/seo/logo.png',
            'social.social_twitter'        => '@example',
            SettingKeys::SITEMAP_ENABLED   => '0',
            SettingKeys::SITEMAP_EXCLUDE   => 'redirect, faq',
            SettingKeys::ROBOTS_RULES      => 'Disallow: /search',
        ]));

        static::assertSame(
            [
                'Example Studio',
                '–',
                TitlePosition::Before,
                'Handmade furniture',
                'We make furniture.',
                ['https://www.example.com/asset/img/seo/share.jpg', 'The workshop', null, null],
                RobotsDirective::NoindexFollow,
                Visibility::Show,
                ['google' => 'g-token', 'bing' => 'b-token', 'pinterest' => 'p-token', 'meta' => 'm-token'],
                ['Example Pty Ltd', OrganisationType::LocalBusiness, 'https://www.example.com/asset/img/seo/logo.png'],
                ['https://x.com/example'],
                [false, ['redirect', 'faq']],
                ['Disallow: /search'],
            ],
            [
                $config->siteName(),
                $config->title->separator,
                $config->title->position,
                $config->title->homeTitle,
                $config->description,
                [
                    $config->shareImage?->url,
                    $config->shareImage?->alt,
                    $config->shareImage?->width,
                    $config->shareImage?->height,
                ],
                $config->robots,
                $config->visibility,
                $config->verification,
                [$config->organisation->name, $config->organisation->type, $config->organisation->logo],
                $config->organisation->profiles,
                [$config->sitemap->enabled, $config->sitemap->excluded],
                $config->robotsRules->lines,
            ],
        );
    }

    #[Test]
    public function swapsTheShareImageAndKeepsTheRest(): void
    {
        $config = SeoConfig::fromSettings(SettingsFactory::site([
            SettingKeys::SHARE_IMAGE     => '/share.jpg',
            SettingKeys::SHARE_IMAGE_ALT => 'Alt',
            SettingKeys::VERIFY_BING     => 'bad token',
            SettingKeys::DESCRIPTION     => 'Described',
            SettingKeys::ROBOTS          => 'noindex',
            SettingKeys::VISIBILITY      => 'hide',
            SettingKeys::ROBOTS_RULES    => 'Disallow: /x',
        ]));
        $image = $config->shareImage?->withSize(1200, 630);
        $sized = $config->withShareImage($image);

        static::assertSame(
            [
                $image,
                $config->site,
                $config->title,
                'Described',
                $config->robots,
                $config->visibility,
                $config->verification,
                $config->organisation,
                $config->sitemap,
                $config->robotsRules,
                $config->problems,
            ],
            [
                $sized->shareImage,
                $sized->site,
                $sized->title,
                $sized->description,
                $sized->robots,
                $sized->visibility,
                $sized->verification,
                $sized->organisation,
                $sized->sitemap,
                $sized->robotsRules,
                $sized->problems,
            ],
        );
    }

    #[Test]
    public function usesTheSitesTitleAsItsNameUnlessOneIsSet(): void
    {
        static::assertSame(
            ['Example', 'Example'],
            [
                SeoConfig::fromSettings(SettingsFactory::site([SettingKeys::SITE_NAME => '  ']))->siteName(),
                SeoConfig::fromCoreSettings(SettingsFactory::site([
                    SettingKeys::SITE_TITLE => ' Example ',
                ]))->siteName(),
            ],
        );
    }
}
