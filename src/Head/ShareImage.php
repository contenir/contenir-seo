<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

/**
 * The image a share preview shows for a page that has none of its own.
 *
 * @api
 */
final readonly class ShareImage
{
    /**
     * The size share previews are drawn at, which a share image should be.
     */
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    /**
     * @param string $url The image's absolute URL
     * @param ?int   $width  In pixels, when known
     * @param ?int   $height In pixels, when known
     */
    public function __construct(
        public string $url,
        public string $alt = '',
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /**
     * The Open Graph and Twitter tags for the image, with a large card.
     *
     * @return list<MetaTag>
     */
    public function tags(): array
    {
        $tags = [MetaTag::property('og:image', $this->url)];
        if (null !== $this->width && null !== $this->height) {
            $tags[] = MetaTag::property('og:image:width', (string) $this->width);
            $tags[] = MetaTag::property('og:image:height', (string) $this->height);
        }

        if ('' !== $this->alt) {
            $tags[] = MetaTag::property('og:image:alt', $this->alt);
        }

        $tags[] = MetaTag::name('twitter:card', 'summary_large_image');
        $tags[] = MetaTag::name('twitter:image', $this->url);
        if ('' !== $this->alt) {
            $tags[] = MetaTag::name('twitter:image:alt', $this->alt);
        }

        return $tags;
    }

    /**
     * The image with its size, as a site measures it.
     */
    public function withSize(int $width, int $height): self
    {
        return new self($this->url, $this->alt, $width, $height);
    }
}
