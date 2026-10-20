<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Verification;

use Contenir\Seo\Verification\Token;
use Contenir\Seo\Verification\Verifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[Group('unit')]
final class TokenTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalid(): array
    {
        return [
            'a space'  => ['abc def'],
            'a quote'  => ['abc"def'],
            'too long' => [str_repeat('a', times: 201)],
            'a tag'    => ['<script>'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pasted(): array
    {
        return [
            'the token'             => [' abc123-_XYZ= ', 'abc123-_XYZ='],
            'Google\'s tag'         => [
                '<meta name="google-site-verification" content="abc123-_XYZ" />',
                'abc123-_XYZ',
            ],
            'single quotes'         => ["<META content='0123ABCD' name='msvalidate.01'>", '0123ABCD'],
            'an entity'             => ['<meta name="x" content=" a&amp;b ">', 'a&b'],
            'an apostrophe entity'  => ['<meta name="x" content="a&apos;b">', "a'b"],
            'across lines'          => ['<meta name="x"
content="a
b">', 'a
b'],
            'a tag with no content' => ['<meta name="x">', '<meta name="x">'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function valid(): array
    {
        return [
            'blank'        => ['  '],
            'Google'       => ['Xr3l9sY0kz_Tq-AbCdEfGhIjKlMnOpQrStUvWxYz012'],
            'Bing'         => ['0123456789ABCDEF0123456789ABCDEF'],
            'base64'       => ['ab+cd/ef=='],
            'with a colon' => ['a:b.c'],
            'the longest'  => [str_repeat('a', times: 200)],
        ];
    }

    #[DataProvider('invalid')]
    #[Test]
    public function explainsATokenThatIsNotOne(string $value): void
    {
        static::assertSame(
            'This doesn\'t look like a Pinterest verification token. Paste the content value of the meta tag Pinterest'
                . ' gives you, or the whole tag.',
            Token::problem(Verifier::Pinterest, $value),
        );
    }

    #[DataProvider('valid')]
    #[Test]
    public function hasNoProblemWithAToken(string $value): void
    {
        static::assertNull(Token::problem(Verifier::Google, $value));
    }

    #[DataProvider('pasted')]
    #[Test]
    public function keepsOnlyTheTokenOfAPastedTag(string $value, string $expected): void
    {
        static::assertSame($expected, Token::normalise($value));
    }
}
