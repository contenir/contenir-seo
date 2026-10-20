<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit;

use Contenir\Seo\SiteUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SiteUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function absolutes(): array
    {
        return [
            'a path'                     => ['/about', 'https://www.example.com/about'],
            'a padded path'              => [' /about ', 'https://www.example.com/about'],
            'an https URL'               => ['https://cdn.example.net/a.jpg', 'https://cdn.example.net/a.jpg'],
            'an http URL'                => ['http://example.org/', 'http://example.org/'],
            'protocol-relative'          => ['//cdn.example.net/a.jpg', 'https://cdn.example.net/a.jpg'],
            'protocol-relative, no host' => ['///a.jpg', null],
            'javascript'                 => ['javascript:alert(1)', null],
            'data'                       => ['data:image/png;base64,AAAA', null],
            'a relative path'            => ['about', null],
            'blank'                      => ['', null],
        ];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function origins(): array
    {
        return [
            'https with a path'        => [
                'https://www.example.com/about',
                'https://www.example.com',
                'www.example.com',
            ],
            'upper case, trailing dot' => ['HTTPS://WWW.Example.COM./', 'https://www.example.com', 'www.example.com'],
            'with a port'              => ['http://localhost:8080', 'http://localhost:8080', 'localhost'],
            'padded'                   => ['  https://example.com  ', 'https://example.com', 'example.com'],
            'no scheme'                => ['www.example.com', '', ''],
            'another scheme'           => ['ftp://example.com', '', ''],
            'no host'                  => ['https:///about', '', ''],
            'blank'                    => ['', '', ''],
        ];
    }

    #[Test]
    public function givesProtocolRelativeUrlsAnHttpOriginsScheme(): void
    {
        static::assertSame(
            'http://cdn.example.net/a.jpg',
            (new SiteUrl('http://localhost'))->absolute('//cdn.example.net/a.jpg'),
        );
    }

    #[Test]
    public function keepsAbsoluteUrlsWithoutAnOrigin(): void
    {
        $site = new SiteUrl();

        static::assertSame(
            [null, 'https://cdn.example.net/a.jpg', 'https://cdn.example.net/b.jpg', false],
            [
                $site->absolute('/about'),
                $site->absolute('https://cdn.example.net/a.jpg'),
                $site->absolute('//cdn.example.net/b.jpg'),
                $site->isSet(),
            ],
        );
    }

    #[DataProvider('absolutes')]
    #[Test]
    public function makesUrlsAbsoluteOnTheOrigin(string $url, ?string $absolute): void
    {
        static::assertSame($absolute, (new SiteUrl('https://www.example.com/'))->absolute($url));
    }

    #[Test]
    public function ownsOnlyUrlsOnItsOwnHost(): void
    {
        $site = new SiteUrl('https://www.example.com');

        static::assertSame(
            [true, true, false, false, false],
            [
                $site->owns('https://www.example.com/about'),
                $site->owns('http://WWW.EXAMPLE.COM:8080/'),
                $site->owns('https://example.com/'),
                $site->owns('/about'),
                (new SiteUrl())->owns('https://www.example.com/'),
            ],
        );
    }

    #[DataProvider('origins')]
    #[Test]
    public function readsTheOriginAndHost(string $url, string $origin, string $host): void
    {
        $site = new SiteUrl($url);

        static::assertSame([$origin, $host, '' !== $origin], [$site->origin, $site->host, $site->isSet()]);
    }
}
