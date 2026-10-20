<?php

declare(strict_types=1);

namespace Contenir\Seo\Head;

/**
 * One meta tag for a page's head. Open Graph tags use the property
 * attribute, and the rest name. The content is plain text; renderers escape
 * it.
 *
 * @api
 */
final readonly class MetaTag
{
    /**
     * @param 'name'|'property' $attribute
     */
    public function __construct(
        public string $attribute,
        public string $key,
        public string $content,
    ) {}

    public static function name(string $key, string $content): self
    {
        return new self('name', $key, $content);
    }

    public static function property(string $key, string $content): self
    {
        return new self('property', $key, $content);
    }
}
