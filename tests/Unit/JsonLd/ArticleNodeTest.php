<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Contact\ContactSettings;
use Contenir\Seo\Head\Article;
use Contenir\Seo\JsonLd\ArticleNode;
use Contenir\Seo\Organisation\Organisation;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ArticleNodeTest extends TestCase
{
    private const string HOME = 'https://www.example.com/';

    #[Test]
    public function describesTheArticleAndTheSitesOwnerAsItsPublisher(): void
    {
        $organisation = new Organisation(
            ContactSettings::fromSettings(['contact' => ['business' => ['name' => 'Example Studio']]]),
            'https://www.example.com/logo.png',
        );
        $article = new Article(
            'https://www.example.com/news/a-story',
            'A story',
            'What happened.',
            'https://www.example.com/asset/story.jpg',
            new DateTimeImmutable('2026-10-01 09:00:00+10:00'),
            new DateTimeImmutable('2026-10-02 10:30:00+10:00'),
            'NewsArticle',
        );

        static::assertSame(
            [
                '@type'         => 'NewsArticle',
                'headline'      => 'A story',
                'url'           => 'https://www.example.com/news/a-story',
                'description'   => 'What happened.',
                'image'         => 'https://www.example.com/asset/story.jpg',
                'datePublished' => '2026-10-01T09:00:00+10:00',
                'dateModified'  => '2026-10-02T10:30:00+10:00',
                'publisher'     => [
                    '@type' => 'Organization',
                    '@id'   => 'https://www.example.com/#organization',
                    'name'  => 'Example Studio',
                    'url'   => self::HOME,
                    'logo'  => 'https://www.example.com/logo.png',
                ],
            ],
            ArticleNode::of($article, $organisation, self::HOME, 'Example'),
        );
    }

    #[Test]
    public function leavesOutWhatTheArticleLacksAndNamesThePublisherAfterTheSite(): void
    {
        static::assertSame(
            [
                '@type'     => 'Article',
                'headline'  => 'A project',
                'url'       => 'https://www.example.com/work/a-project',
                'publisher' => [
                    '@type' => 'Organization',
                    '@id'   => 'https://www.example.com/#organization',
                    'name'  => 'Example',
                    'url'   => self::HOME,
                ],
            ],
            ArticleNode::of(
                new Article('https://www.example.com/work/a-project', 'A project'),
                new Organisation(),
                self::HOME,
                'Example',
            ),
        );
    }
}
