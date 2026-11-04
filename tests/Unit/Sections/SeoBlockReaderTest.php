<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\SeoBlock;
use Contenir\Seo\Sections\SeoBlockReader;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

#[Group('unit')]
final class SeoBlockReaderTest extends TestCase
{
    private static function read(string $xml): ?SeoBlock
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document->documentElement instanceof DOMElement
            ? SeoBlockReader::read($document->documentElement)
            : null;
    }

    #[Test]
    public function aBlankSchemaOrAdvancedColumnIsNone(): void
    {
        $block = self::read('<seo type="project" schema=" "><advanced> </advanced></seo>');

        static::assertSame([null, null], [$block?->schema, $block?->advanced]);
    }

    #[Test]
    public function aBlockThatNamesNoRolesTakesTheConventions(): void
    {
        static::assertEquals(new SeoBlock('page'), self::read('<seo type="page"/>'));
    }

    #[Test]
    public function aBlockWithoutATypeIsNone(): void
    {
        static::assertSame([null, null], [self::read('<seo/>'), self::read('<seo type=" "/>')]);
    }

    #[Test]
    public function marksOnlyTheRolesSaidToBeRequired(): void
    {
        static::assertSame(
            ['description'],
            self::read(
                '<seo type="project"><title required="false">title</title>'
                    . '<description required="true">abstract</description></seo>',
            )?->required,
        );
    }

    #[Test]
    public function readsEachRolesColumnsInOrderTrimmedWithoutGaps(): void
    {
        static::assertSame(
            [
                'title'       => ['meta_title', 'title'],
                'description' => ['meta_description', 'abstract'],
                'image'       => ['image_lg', 'thumbnail'],
                'image@alt'   => ['alt', 'title'],
                'published'   => ['publish'],
            ],
            self::read(
                '<seo type="project"><!-- roles --> <description> meta_description, ,abstract </description>'
                    . '<image alt="alt, title">image_lg, thumbnail</image><published>publish</published></seo>',
            )?->roles,
        );
    }

    #[Test]
    public function readsTheSchemaAndTheAdvancedColumn(): void
    {
        $block = self::read(
            '<seo type="project" schema=" CreativeWork "><advanced> seo, other </advanced><title>name</title></seo>',
        );

        static::assertSame(['CreativeWork', 'seo', ['name'], false], [
            $block?->schema,
            $block?->advanced,
            $block?->roles['title'] ?? null,
            array_key_exists('advanced', $block->roles ?? []),
        ]);
    }

    #[Test]
    public function theTypeIsTrimmed(): void
    {
        static::assertSame('project', self::read('<seo type=" project "/>')?->type);
    }
}
