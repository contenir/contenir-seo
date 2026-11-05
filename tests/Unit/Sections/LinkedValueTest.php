<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\LinkedValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class LinkedValueTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function notLinks(): array
    {
        return [
            'a column'               => ['image_lg'],
            'an empty source'        => [''],
            'no column'              => ['assets.'],
            'no relation'            => ['.path'],
            'two steps'              => ['assets.file.path'],
            'text before it'         => ['see assets.path'],
            'text after it'          => ["assets.path\nmore"],
            'a capitalised column'   => ['assets.Path'],
            'a filter with no value' => ['assets[type=].path'],
            'a filter with no field' => ['assets[=hero].path'],
            'a filter without equals' => ['assets[type].path'],
            'a value with a space'   => ['assets[type=a b].path'],
            'a filter with no column' => ['assets[type=hero]'],
        ];
    }

    #[Test]
    #[DataProvider('notLinks')]
    public function whatIsNotALinkParsesAsNothing(string $source): void
    {
        static::assertNull(LinkedValue::parse($source));
    }

    #[Test]
    public function aLinkNamesItsRelationAndColumn(): void
    {
        $link = LinkedValue::parse('heroImage.path');

        static::assertSame(
            ['heroImage', 'path', null, null],
            [$link?->relation, $link?->column, $link?->field, $link?->value],
        );
    }

    #[Test]
    public function aLinkCanNameWhichOfTheRecordsItWants(): void
    {
        $link = LinkedValue::parse('assets[type=hero].path');

        static::assertSame(
            ['assets', 'path', 'type', 'hero'],
            [$link?->relation, $link?->column, $link?->field, $link?->value],
        );
    }

    #[Test]
    public function aListWithALinkInItIsToldFromOneWithout(): void
    {
        static::assertTrue(LinkedValue::any(['image_lg', 'assets.path']));
        static::assertFalse(LinkedValue::any(['image_lg', 'image_sm']));
        static::assertFalse(LinkedValue::any([]));
    }

    #[Test]
    public function theFirstMatchingRecordWithAValueWins(): void
    {
        $link = new LinkedValue('assets', 'path', 'type', 'hero');

        static::assertSame('/b.jpg', $link->from([
            ['type' => 'gallery', 'path' => '/a.jpg'],
            ['type' => 'hero', 'path' => '  '],
            ['type' => 'hero', 'path' => '/b.jpg'],
            ['type' => 'hero', 'path' => '/c.jpg'],
        ]));
    }

    #[Test]
    public function withoutAFilterAnyRecordWillDo(): void
    {
        static::assertSame('/a.jpg', (new LinkedValue('assets', 'path'))->from([
            ['path' => null],
            ['path' => '/a.jpg'],
        ]));
    }

    #[Test]
    public function aFilterComparesScalarsAsText(): void
    {
        static::assertSame('/a.jpg', (new LinkedValue('assets', 'path', 'sequence', '2'))->from([
            ['sequence' => 1, 'path' => '/z.jpg'],
            ['sequence' => 2, 'path' => '/a.jpg'],
        ]));
    }

    #[Test]
    public function aRecordWithoutTheFilteredColumnIsNoMatch(): void
    {
        static::assertNull((new LinkedValue('assets', 'path', 'type', 'hero'))->from([
            ['path' => '/a.jpg'],
            ['type' => ['hero'], 'path' => '/b.jpg'],
        ]));
    }

    #[Test]
    public function aValueThatIsNotTextIsPassedOver(): void
    {
        static::assertNull((new LinkedValue('assets', 'path'))->from([['path' => ['/a.jpg']], []]));
    }

    #[Test]
    public function aNumberIsGivenBackAsText(): void
    {
        static::assertSame('12', (new LinkedValue('assets', 'path'))->from([['path' => 12]]));
    }
}
