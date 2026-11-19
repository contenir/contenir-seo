<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\JsonLd;

use Contenir\Contact\ContactSettings;
use Contenir\Seo\Head\Article;
use Contenir\Seo\JsonLd\ArticleNode;
use Contenir\Seo\Organisation\Organisation;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

#[Group('unit')]
final class ArticleNodeTest extends TestCase
{
    private const string HOME = 'https://www.example.com/';

    /**
     * @return array<string, array{string}>
     */
    public static function articleTypeProvider(): array
    {
        return ['article' => ['Article'], 'news' => ['NewsArticle'], 'blog' => ['BlogPosting']];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authoredTypeProvider(): array
    {
        return ['an article' => ['Article'], 'any other type' => ['CreativeWork']];
    }

    #[Test]
    public function describesAnyOtherTypeByItsNameAndTheSiteItIsPartOf(): void
    {
        static::assertSame(
            [
                '@type'        => 'CreativeWork',
                'name'         => 'A project',
                'url'          => 'https://www.example.com/work/a-project',
                'description'  => 'What we did.',
                'image'        => 'https://www.example.com/asset/project.jpg',
                'dateCreated'  => '2026-10-01T09:00:00+10:00',
                'dateModified' => '2026-10-02T10:30:00+10:00',
                'isPartOf'     => ['@id' => 'https://www.example.com/#website'],
                'publisher'    => [
                    '@type' => 'Organization',
                    '@id'   => 'https://www.example.com/#organization',
                    'name'  => 'Example',
                    'url'   => self::HOME,
                ],
            ],
            ArticleNode::of(
                new Article(
                    'https://www.example.com/work/a-project',
                    'A project',
                    'What we did.',
                    'https://www.example.com/asset/project.jpg',
                    new DateTimeImmutable('2026-10-01 09:00:00+10:00'),
                    new DateTimeImmutable('2026-10-02 10:30:00+10:00'),
                    'CreativeWork',
                ),
                new Organisation(),
                self::HOME,
                'Example',
            ),
        );
    }

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
    #[DataProvider('articleTypeProvider')]
    public function eachArticleTypeHasAHeadline(string $type): void
    {
        static::assertSame(
            'A story',
            ArticleNode::of(
                new Article(self::HOME, 'A story', type: $type),
                new Organisation(),
                self::HOME,
                'X',
            )['headline']
                ?? null,
        );
    }

    #[Test]
    public function givesNoAuthorLinksWithoutAnAuthor(): void
    {
        static::assertArrayNotHasKey(
            'author',
            ArticleNode::of(
                new Article(
                    self::HOME,
                    'A story',
                    authorUrl: 'https://joroca.com',
                    authorProfiles: ['https://x.com/jo'],
                ),
                new Organisation(),
                self::HOME,
                'Example',
            ),
        );
    }

    #[Test]
    public function givesTheAuthorTheirOwnPageAndProfiles(): void
    {
        static::assertSame(
            [
                '@type'  => 'Person',
                'name'   => 'Jo Roca',
                'url'    => 'https://joroca.com',
                'sameAs' => ['https://www.instagram.com/joroca', 'https://twitter.com/joroca'],
            ],
            ArticleNode::of(
                new Article(
                    self::HOME,
                    'Jo Roca',
                    author: 'Jo Roca',
                    authorUrl: 'https://joroca.com',
                    authorProfiles: ['https://www.instagram.com/joroca', 'https://twitter.com/joroca'],
                ),
                new Organisation(),
                self::HOME,
                'Example',
            )['author']
                ?? null,
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

    #[Test]
    #[DataProvider('authoredTypeProvider')]
    public function namesTheAuthorAsAPerson(string $type): void
    {
        static::assertSame(
            ['@type' => 'Person', 'name' => 'Jo Roca'],
            ArticleNode::of(
                new Article(self::HOME, 'Jo Roca', type: $type, author: 'Jo Roca'),
                new Organisation(),
                self::HOME,
                'Example',
            )['author']
                ?? null,
        );
    }

    #[Test]
    public function writesTheAuthorBeforeThePublisher(): void
    {
        static::assertSame(
            ['@type', 'headline', 'url', 'author', 'publisher'],
            array_keys(ArticleNode::of(
                new Article(self::HOME, 'Jo Roca', author: 'Jo Roca'),
                new Organisation(),
                self::HOME,
                'Example',
            )),
        );
    }
}
