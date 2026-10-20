<?php

declare(strict_types=1);

namespace Contenir\Seo\Tests\Unit\Head;

use Contenir\Seo\Head\MetaTag;
use Contenir\Seo\Head\ShareImage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class ShareImageTest extends TestCase
{
    /**
     * @param list<MetaTag> $tags
     *
     * @return list<string>
     */
    private static function lines(array $tags): array
    {
        return array_map(static fn(MetaTag $tag): string => "{$tag->attribute} {$tag->key}={$tag->content}", $tags);
    }

    #[Test]
    public function describesAnImageOfUnknownSizeWithoutAlt(): void
    {
        static::assertSame(
            [
                'property og:image=https://www.example.com/share.jpg',
                'name twitter:card=summary_large_image',
                'name twitter:image=https://www.example.com/share.jpg',
            ],
            self::lines((new ShareImage('https://www.example.com/share.jpg'))->tags()),
        );
    }

    #[Test]
    public function describesAnImageWithItsSizeAndAlt(): void
    {
        $image = (new ShareImage('https://www.example.com/share.jpg', 'Our studio'))->withSize(1200, 630);

        static::assertSame(
            [
                'property og:image=https://www.example.com/share.jpg',
                'property og:image:width=1200',
                'property og:image:height=630',
                'property og:image:alt=Our studio',
                'name twitter:card=summary_large_image',
                'name twitter:image=https://www.example.com/share.jpg',
                'name twitter:image:alt=Our studio',
            ],
            self::lines($image->tags()),
        );
    }

    #[Test]
    public function leavesOutASizeThatIsHalfKnown(): void
    {
        static::assertSame(
            [
                'property og:image=https://www.example.com/share.jpg',
                'name twitter:card=summary_large_image',
                'name twitter:image=https://www.example.com/share.jpg',
            ],
            self::lines((new ShareImage('https://www.example.com/share.jpg', width: 1200))->tags()),
        );
    }

    #[Test]
    public function recommendsTheSizeSharePreviewsAreDrawnAt(): void
    {
        static::assertSame([1200, 630], [ShareImage::WIDTH, ShareImage::HEIGHT]);
    }
}
