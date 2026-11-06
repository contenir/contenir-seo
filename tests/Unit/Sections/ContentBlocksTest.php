<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\ContentBlocks;
use Contenir\Seo\Sections\SeoBlock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function json_encode;

#[Group('unit')]
final class ContentBlocksTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function nothing(): array
    {
        return [
            'null'                            => [null],
            'an empty string'                 => [''],
            'text that is no json'            => ['not json'],
            'a number'                        => [5],
            'a list of strings'               => [['a', 'b']],
            'blocks without fields'           => [[['type' => 'text'], ['type' => 'media', 'fields' => 'x']]],
            'an images field that is no list' => [[['type' => 'media', 'fields' => ['images' => '3']]]],
            'a description that is no text'   => [[['type' => 'text', 'fields' => ['description' => ['a']]]]],
        ];
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private static function block(string $type, array $fields, string $status = '1'): array
    {
        return ['id' => '', 'type' => $type, 'status' => $status, 'fields' => $fields];
    }

    /**
     * @param list<array<string, mixed>> $blocks
     */
    private static function json(array $blocks): string
    {
        return (string) json_encode($blocks);
    }

    #[Test]
    public function aBlocksOwnColumnsComeBeforeContent(): void
    {
        $block = new SeoBlock('project', roles: ['description' => ['summary'], 'image' => ['hero']]);
        $page  = $block->page([
            'summary'             => 'Own words',
            'hero'                => '/own.jpg',
            ContentBlocks::COLUMN => self::json([self::block('text', ['description' => 'Content'])]),
            ContentBlocks::IMAGE  => '/content.jpg',
        ]);

        static::assertSame(['Own words', '/own.jpg'], [$page->description, $page->image]);
    }

    #[Test]
    public function aBlockUsesContentWhereItsColumnsGiveNothing(): void
    {
        $block = new SeoBlock('project');
        $page  = $block->page([
            ContentBlocks::COLUMN => self::json([self::block('text', ['description' => '<p>From content.</p>'])]),
            ContentBlocks::IMAGE  => '/asset/a.jpg',
        ]);

        static::assertSame(['<p>From content.</p>', '/asset/a.jpg'], [$page->description, $page->image]);
    }

    #[Test]
    public function aBlockWithNoStatusKeyCountsAsOn(): void
    {
        $blocks = ContentBlocks::of(self::json([['type' => 'text', 'fields' => ['description' => 'Words.']]]));

        static::assertSame('Words.', $blocks->description());
    }

    #[Test]
    public function aContentImageOfOnlySpacesIsNoImage(): void
    {
        static::assertNull((new SeoBlock('project'))->page([ContentBlocks::IMAGE => '  '])->image);
    }

    #[Test]
    public function aContentImageThatIsNoTextIsNoImage(): void
    {
        static::assertNull((new SeoBlock('project'))->page([ContentBlocks::IMAGE => ['a']])->image);
    }

    #[Test]
    public function aMediaBlockSwitchedOffGivesNoImages(): void
    {
        $blocks = ContentBlocks::of(self::json([self::block('media', ['images' => ['1']], status: '')]));

        static::assertSame([], $blocks->imageIds());
    }

    #[Test]
    public function aStatusOfOnlySpacesIsOff(): void
    {
        $blocks = ContentBlocks::of(self::json([self::block('text', ['description' => 'Words.'], status: '  ')]));

        static::assertNull($blocks->description());
    }

    #[Test]
    #[DataProvider('nothing')]
    public function givesNothingForWhatIsNotBlocks(mixed $json): void
    {
        $blocks = ContentBlocks::of($json);

        static::assertSame([null, []], [$blocks->description(), $blocks->imageIds()]);
    }

    #[Test]
    public function leavesOutABlockSwitchedOffAndOneWithOnlyMarkup(): void
    {
        $blocks = ContentBlocks::of(self::json([
            self::block('text', ['description' => '<p>Hidden.</p>'], status: ''),
            self::block('text', ['description' => '<p> &nbsp; </p><br>']),
            self::block('credit', ['description' => '<p>Photography.</p>']),
            self::block('text', ['description' => '<p>Shown.</p>']),
        ]));

        static::assertSame('<p>Shown.</p>', $blocks->description());
    }

    #[Test]
    public function skipsABlankImageIdAndKeepsTheOnesAfterIt(): void
    {
        $blocks = ContentBlocks::of(self::json([self::block('media', ['images' => ['', '  ', '4', '5']])]));

        static::assertSame(['4', '5'], $blocks->imageIds());
    }

    #[Test]
    public function takesTheFirstTextBlocksDescriptionAsWritten(): void
    {
        $blocks = ContentBlocks::of(self::json([
            self::block('media', ['images' => ['3']]),
            self::block('text', ['description' => '<p>A house by the sea.</p>']),
            self::block('text', ['description' => '<p>Second.</p>']),
        ]));

        static::assertSame('<p>A house by the sea.</p>', $blocks->description());
    }

    #[Test]
    public function takesTheImagesOfTheFirstMediaBlockThatHasAny(): void
    {
        $blocks = ContentBlocks::of(self::json([
            self::block('media', ['images' => []]),
            self::block('carousel', ['images' => ['7', 8, '', null]]),
            self::block('media', ['images' => ['9']]),
        ]));

        static::assertSame(['7', '8'], $blocks->imageIds());
    }

    #[Test]
    public function wordsHiddenInEntitiesThatAreOnlySpaceDoNotCount(): void
    {
        $blocks = ContentBlocks::of([
            self::block('text', ['description' => '<p>&NewLine;&Tab;</p>']),
            self::block('text', ['description' => "\xB1\x31"]),
            self::block('text', ['description' => 'Real words.']),
        ]);

        static::assertSame('Real words.', $blocks->description());
    }
}
