<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Visibility;

use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\SiteUrl;
use Contenir\Seo\Visibility\Visibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class VisibilityTest extends TestCase
{
    /**
     * @return array<string, array{Visibility, string, string, bool}>
     */
    public static function hosts(): array
    {
        return [
            'automatic, production'             => [
                Visibility::Automatic,
                'https://www.example.com',
                'www.example.com',
                false,
            ],
            'automatic, production in capitals' => [
                Visibility::Automatic,
                'https://www.example.com',
                ' WWW.Example.COM. ',
                false,
            ],
            'automatic, staging'                => [
                Visibility::Automatic,
                'https://www.example.com',
                'staging.example.com',
                true,
            ],
            'automatic, the bare domain'        => [
                Visibility::Automatic,
                'https://www.example.com',
                'example.com',
                true,
            ],
            'automatic, without a site URL'     => [Visibility::Automatic, '', 'staging.example.com', false],
            'hide, production'                  => [
                Visibility::Hide,
                'https://www.example.com',
                'www.example.com',
                true,
            ],
            'show, staging'                     => [
                Visibility::Show,
                'https://www.example.com',
                'staging.example.com',
                false,
            ],
        ];
    }

    /**
     * @return array<string, array{?string, Visibility}>
     */
    public static function stored(): array
    {
        return [
            'auto'    => ['auto', Visibility::Automatic],
            'hide'    => [' HIDE ', Visibility::Hide],
            'show'    => ['show', Visibility::Show],
            'unknown' => ['1', Visibility::Automatic],
            'missing' => [null, Visibility::Automatic],
        ];
    }

    #[DataProvider('hosts')]
    #[Test]
    public function hidesCopiesThatAreNotAtTheSiteUrl(
        Visibility $visibility,
        string $siteUrl,
        string $host,
        bool $hidden,
    ): void {
        static::assertSame($hidden, $visibility->hides(new SiteUrl($siteUrl), $host));
    }

    #[Test]
    public function offersEachChoiceWithALabel(): void
    {
        static::assertSame(
            [
                [
                    'auto' => 'Automatic: hide every copy that isn\'t at the site URL',
                    'hide' => 'Hide this copy from search engines',
                    'show' => 'Show this copy to search engines',
                ],
                RobotsDirective::NoindexNofollow,
            ],
            [Visibility::options(), Visibility::HIDDEN],
        );
    }

    #[DataProvider('stored')]
    #[Test]
    public function readsTheStoredChoice(?string $value, Visibility $visibility): void
    {
        static::assertSame($visibility, Visibility::parse($value));
    }
}
