<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\LinkedValue;
use Contenir\Seo\Sections\MappedPage;
use Contenir\Seo\Sections\RecordOverrides;
use Contenir\Seo\Sections\SeoBlock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SeoBlockTest extends TestCase
{
    #[Test]
    public function aBlockListsTheValuesItReadsFromLinkedRecordsByTheirNames(): void
    {
        $block = new SeoBlock('page', roles: [
            'image'     => ['assets[type=hero].path', 'image_lg'],
            'image@alt' => ['hero.alt'],
        ]);

        static::assertEquals(
            [
                'assets[type=hero].path' => new LinkedValue('assets', 'path', 'type', 'hero'),
                'hero.alt'               => new LinkedValue('hero', 'alt'),
            ],
            $block->linked(),
        );
    }

    #[Test]
    public function aBlockReadsALinkedValueFromTheRowUnderItsName(): void
    {
        $block = new SeoBlock('page', roles: ['image' => ['assets.path']]);

        static::assertSame('/a.jpg', $block->page(['assets.path' => '/a.jpg'])->image);
    }

    #[Test]
    public function aBlockReadsItsAdvancedColumnFirstAndEachColumnOnce(): void
    {
        static::assertSame(
            ['seo', 'meta_title', 'title', 'meta_description', 'description'],
            (new SeoBlock('page', advanced: 'seo'))->columns(),
        );
    }

    #[Test]
    public function aBlockWithoutAnAdvancedColumnReadsOnlyItsRoles(): void
    {
        static::assertSame(['title'], (new SeoBlock('page', roles: ['title' => ['title']]))->columns());
    }

    #[Test]
    public function aNumberReadsAsText(): void
    {
        static::assertSame('2024', (new SeoBlock('award'))->page(['title' => 2024])->title);
    }

    #[Test]
    public function aRecordsOverridesComeFromItsAdvancedColumn(): void
    {
        $page = (new SeoBlock('project', schema: 'CreativeWork', advanced: 'seo'))->page([
            'seo' => '{"canonical": "/work/house", "robots": "noindex, follow", "schema": "Article"}',
        ]);

        static::assertSame(['/work/house', 'noindex, follow', 'Article'], [
            $page->canonical,
            $page->robots,
            $page->schema,
        ]);
    }

    #[Test]
    public function aRecordsShareValuesComeFromItsAdvancedColumn(): void
    {
        $page = (new SeoBlock('project', advanced: 'seo'))->page([
            'seo' => '{"title": "Share", "description": "Shared.", "image": "/share.jpg", "image_alt": "Alt"}',
        ]);

        static::assertEquals(new RecordOverrides('Share', 'Shared.', '/share.jpg', 'Alt'), $page->overrides);
    }

    #[Test]
    public function aRecordWithoutAnAdvancedColumnHasNoOverrides(): void
    {
        static::assertEquals(new RecordOverrides(), (new SeoBlock('project'))->page(['title' => 'House'])->overrides);
    }

    #[Test]
    public function aRoleTheBlockDoesNotMapReadsNothing(): void
    {
        static::assertNull((new SeoBlock('page', roles: []))->page(['title' => 'House'])->title);
    }

    #[Test]
    public function eachRoleTakesItsFirstColumnWithSomethingInIt(): void
    {
        $block = new SeoBlock('project', roles: [
            'title'       => ['meta_title', 'title'],
            'description' => ['meta_description', 'abstract', 'description'],
            'image'       => ['image_lg', 'thumbnail'],
            'image@alt'   => ['caption', 'title'],
        ]);

        static::assertEquals(
            new MappedPage('House', 'A house.', '/house.jpg', 'House'),
            $block->page([
                'meta_title'       => '  ',
                'title'            => 'House',
                'meta_description' => ['not', 'text'],
                'abstract'         => 'A house.',
                'description'      => 'Ignored.',
                'image_lg'         => null,
                'thumbnail'        => '/house.jpg',
            ]),
        );
    }

    #[Test]
    public function knowsWhichRolesARecordMustFill(): void
    {
        $block = new SeoBlock('project', required: ['description']);

        static::assertSame([true, false], [$block->requires('description'), $block->requires('title')]);
    }

    #[Test]
    public function theBlocksSchemaStandsWithoutAnOverride(): void
    {
        $block = new SeoBlock('project', schema: 'CreativeWork', advanced: 'seo');

        static::assertSame(['CreativeWork', 'CreativeWork'], [
            $block->page(['seo' => '{}'])->schema,
            (new SeoBlock('project', schema: 'CreativeWork'))->page(['seo' => '{"schema": "Article"}'])->schema,
        ]);
    }
}
