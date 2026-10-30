<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Head;

use Contenir\Seo\Head\Article;
use Contenir\Seo\Head\HeadBuilder;
use Contenir\Seo\Head\MetaTag;
use Contenir\Seo\Head\PageContext;
use Contenir\Seo\SeoConfig;
use Contenir\Seo\SettingKeys;
use Contenir\Seo\Tests\TestAsset\Factory\SettingsFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;
use function array_map;

#[Group('unit')]
final class HeadBuilderTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private static function builder(array $values = []): HeadBuilder
    {
        return new HeadBuilder(SeoConfig::fromSettings(SettingsFactory::site($values)));
    }

    /**
     * @param list<MetaTag> $tags
     *
     * @return list<string>
     */
    private static function lines(array $tags): array
    {
        return array_map(static fn(MetaTag $tag): string => "{$tag->attribute} {$tag->key}={$tag->content}", $tags);
    }

    #[Test]
    public function addsTheDefaultDescriptionAndImageToAPageWithout(): void
    {
        $head = self::builder([
            SettingKeys::DESCRIPTION     => 'Handmade furniture',
            SettingKeys::SHARE_IMAGE     => '/asset/img/seo/share.jpg',
            SettingKeys::SHARE_IMAGE_ALT => 'Our studio',
        ])->build(new PageContext('About', host: 'www.example.com'));

        static::assertSame(
            [
                'property og:site_name=Example',
                'name description=Handmade furniture',
                'property og:description=Handmade furniture',
                'name twitter:description=Handmade furniture',
                'property og:image=https://www.example.com/asset/img/seo/share.jpg',
                'property og:image:alt=Our studio',
                'name twitter:card=summary_large_image',
                'name twitter:image=https://www.example.com/asset/img/seo/share.jpg',
                'name twitter:image:alt=Our studio',
            ],
            self::lines($head->tags),
        );
    }

    #[Test]
    public function addsTheSitesRobotsDefaultWhenItIsNotToIndexAndFollow(): void
    {
        $head = self::builder([SettingKeys::ROBOTS => 'index, nofollow'])->build(
            new PageContext(host: 'www.example.com'),
        );

        static::assertSame(['property og:site_name=Example', 'name robots=index, nofollow'], self::lines($head->tags));
    }

    #[Test]
    public function addsVerificationAndStructuredDataOnlyOnTheHomePage(): void
    {
        $builder = self::builder([
            SettingKeys::VERIFY_GOOGLE => 'google-token',
            SettingKeys::VERIFY_META   => 'meta-token',
            SettingKeys::HOME_TITLE    => 'Example: handmade furniture',
        ]);

        $home  = $builder->build(new PageContext('Home', home: true, host: 'www.example.com'));
        $other = $builder->build(new PageContext('Home', host: 'www.example.com'));

        static::assertSame(
            [
                [
                    'property og:site_name=Example',
                    'name google-site-verification=google-token',
                    'name facebook-domain-verification=meta-token',
                ],
                'Example: handmade furniture',
                'https://www.example.com/#website',
                ['property og:site_name=Example'],
                'Home | Example',
                [],
            ],
            [
                self::lines($home->tags),
                $home->title,
                $home->jsonLd[1]['@id'] ?? null,
                self::lines($other->tags),
                $other->title,
                $other->jsonLd,
            ],
        );
    }

    #[Test]
    public function aPageThatIsNoArticleHasOnlyItsTrail(): void
    {
        $head = self::builder()->build(new PageContext('About', host: 'www.example.com', path: '/about'));

        static::assertSame(['BreadcrumbList'], array_column($head->jsonLd, '@type'));
    }

    #[Test]
    public function describesAnArticleAndItsTrailOnAnotherPage(): void
    {
        $head = self::builder()
            ->build(new PageContext(
                'A project',
                host: 'www.example.com',
                path: '/work/a-project',
                article: new Article('https://www.example.com/work/a-project', 'A project'),
            ));

        static::assertSame(
            [
                ['Article',        'A project'],
                ['BreadcrumbList', 'https://www.example.com/work'],
            ],
            [
                [$head->jsonLd[0]['@type'] ?? null, $head->jsonLd[0]['headline'] ?? null],
                [$head->jsonLd[1]['@type'] ?? null, $head->jsonLd[1]['itemListElement'][1]['item'] ?? null],
            ],
        );
    }

    #[Test]
    public function hasNoPageStructuredDataWithoutASiteUrl(): void
    {
        $head = (new HeadBuilder(SeoConfig::fromSettings(SettingsFactory::make([
            SettingKeys::SITE_TITLE => 'Example',
        ]))))
            ->build(new PageContext(
                'A project',
                path: '/work/a-project',
                article: new Article('/work/a-project', 'A project'),
            ));

        static::assertSame([], $head->jsonLd);
    }

    #[Test]
    public function hidesACopyThatIsNotAtTheSiteUrl(): void
    {
        $head = self::builder()->build(new PageContext('About', host: 'staging.example.com'));

        static::assertSame(
            [true, ['property og:site_name=Example', 'name robots=noindex, nofollow']],
            [$head->hidden, self::lines($head->tags)],
        );
    }

    #[Test]
    public function leavesAPagesOwnDescriptionAndImageAlone(): void
    {
        $head = self::builder([
            SettingKeys::DESCRIPTION => 'Handmade furniture',
            SettingKeys::SHARE_IMAGE => '/share.jpg',
        ])->build(new PageContext('About', host: 'www.example.com', hasDescription: true, hasImage: true));

        static::assertSame(
            [false, 'About | Example', ['property og:site_name=Example']],
            [$head->hidden, $head->title, self::lines($head->tags)],
        );
    }

    #[Test]
    public function writesNoSiteNameForASiteWithout(): void
    {
        $head = (new HeadBuilder(new SeoConfig()))->build(new PageContext('About'));

        static::assertSame(['About', [], false], [$head->title, $head->tags, $head->hidden]);
    }
}
