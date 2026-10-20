<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Title;

use Contenir\Seo\Title\TitlePosition;
use Contenir\Seo\Title\TitleTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

#[Group('unit')]
final class TitleTemplateTest extends TestCase
{
    /**
     * @return array<string, array{TitleTemplate, ?string, string}>
     */
    public static function titles(): array
    {
        $after = new TitleTemplate('Example');

        return [
            'after, by default'      => [$after, 'About us', 'About us | Example'],
            'before'                 => [
                new TitleTemplate('Example', '–', TitlePosition::Before),
                'About us',
                'Example – About us',
            ],
            'left out'               => [
                new TitleTemplate('Example', '|', TitlePosition::None),
                'About us',
                'About us',
            ],
            'a padded separator'     => [new TitleTemplate('Example', ' / '), 'About us', 'About us / Example'],
            'a blank separator'      => [new TitleTemplate('Example', '  '), 'About us', 'About us Example'],
            'a page without a title' => [$after, null, 'Example'],
            'a blank title'          => [$after, '  ', 'Example'],
            'the site name as title' => [$after, ' Example ', 'Example'],
            'no site name'           => [new TitleTemplate(), ' About us ', 'About us'],
            'a padded site name'     => [new TitleTemplate(' Example '), 'About us', 'About us | Example'],
        ];
    }

    #[DataProvider('titles')]
    #[Test]
    public function buildsAPagesTitle(TitleTemplate $template, ?string $title, string $expected): void
    {
        static::assertSame($expected, $template->format($title));
    }

    #[Test]
    public function buildsTheHomeTitleLikeAnyOtherWithoutAnOverride(): void
    {
        static::assertSame('Home | Example', (new TitleTemplate('Example', homeTitle: '  '))->home('Home'));
    }

    #[Test]
    public function offersTheSeparatorsAndPositionsWithLabels(): void
    {
        static::assertSame(
            [
                ['|', '–', '-', '·', '•', '/', ':'],
                ['after' => 'After the page title', 'before' => 'Before the page title', 'none' => 'Leave it out'],
                '| (vertical bar)',
            ],
            [array_keys(TitleTemplate::SEPARATORS), TitlePosition::options(), TitleTemplate::SEPARATORS['|']],
        );
    }

    #[Test]
    public function usesTheHomeTitleAsItIsOnTheHomePage(): void
    {
        $template = new TitleTemplate('Example', homeTitle: ' Example: handmade furniture ');

        static::assertSame(
            ['Example: handmade furniture', 'Home | Example'],
            [$template->home('Home'), $template->format('Home')],
        );
    }
}
