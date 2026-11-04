<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Sections;

use Contenir\Seo\Sections\Overrides;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class OverridesTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function noneProvider(): array
    {
        return [
            'nothing'            => [null],
            'not text'           => [['robots' => 'noindex']],
            'not json'           => ['{robots'],
            'json but no object' => ['"noindex"'],
        ];
    }

    #[Test]
    public function aNonTextOverrideIsLeftOut(): void
    {
        static::assertSame(['robots' => 'noindex'], Overrides::from('{"canonical": 1, "robots": "noindex"}'));
    }

    #[Test]
    #[DataProvider('noneProvider')]
    public function givesNoneWithoutAJsonObject(mixed $json): void
    {
        static::assertSame([], Overrides::from($json));
    }

    #[Test]
    public function keepsOnlyTheKnownOverridesThatHoldText(): void
    {
        static::assertSame(
            ['canonical' => '/work/house', 'schema' => 'CreativeWork'],
            Overrides::from(
                '{"canonical": " /work/house ", "robots": " ", "schema": "CreativeWork", "title": "T", "image": 3}',
            ),
        );
    }
}
