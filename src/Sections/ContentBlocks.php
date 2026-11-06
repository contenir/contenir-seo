<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use function html_entity_decode;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function preg_replace;
use function strip_tags;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * What a record's Section field (its blocks, kept as JSON) can give a page where its own columns give
 * nothing: a description from the first text block that has one, and the images of the first media block.
 * A block switched off, with an empty status, is left out, as it isn't on the page.
 *
 * @mago-expect lint:cyclomatic-complexity Each kind of value is read and checked on its own, a branch apiece.
 * @mago-expect lint:kan-defect Each kind of value is read and checked on its own, a branch apiece.
 *
 * @api
 */
final readonly class ContentBlocks
{
    /** The column a record's blocks are kept in, by convention. */
    public const string COLUMN = 'section';

    /** The key a record's row holds the first content image's path under, which a site's resolver puts there. */
    public const string IMAGE = '@content.image';

    /** The blocks whose text describes a page. */
    private const array TEXT = ['text'];

    /** The blocks whose images stand for a page. */
    private const array MEDIA = ['media', 'carousel'];

    /**
     * @param list<array{type: string, fields: array<string, mixed>}> $blocks Those switched on, in their order
     */
    private function __construct(
        private array $blocks,
    ) {}

    /**
     * The first content image's path, which the site's resolver puts in $row under {@see self::IMAGE}.
     *
     * @param array<string, mixed> $row
     */
    public static function imagePath(array $row): ?string
    {
        $path = trim(self::scalar($row[self::IMAGE] ?? null));

        return '' === $path ? null : $path;
    }

    /**
     * The blocks held in $json, as a record's column gives them.
     */
    public static function of(mixed $json): self
    {
        /** @var mixed $decoded */
        $decoded = is_string($json) ? json_decode($json, associative: true) : $json;
        $blocks  = [];
        /** @var mixed $block */
        foreach (is_array($decoded) ? $decoded : [] as $block) {
            if (! is_array($block) || '' === trim(self::scalar($block['status'] ?? '1'))) {
                continue;
            }

            $blocks[] = [
                'type'   => self::scalar($block['type'] ?? ''),
                'fields' => self::array($block['fields'] ?? null),
            ];
        }

        return new self($blocks);
    }

    /**
     * @return array<string, mixed>
     */
    private static function array(mixed $value): array
    {
        /** @var array<string, mixed> */
        return is_array($value) ? $value : [];
    }

    /**
     * @param array<array-key, mixed> $images
     *
     * @return list<string>
     */
    private static function ids(array $images): array
    {
        $ids = [];
        /** @var mixed $image */
        foreach ($images as $image) {
            $id = self::scalar($image);
            if ('' === trim($id)) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    private static function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function words(string $html): bool
    {
        $plain = strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8'));

        return '' !== (string) preg_replace('/[\s\x{00A0}]+/u', replacement: '', subject: $plain);
    }

    /**
     * The first text block's description that holds any words, with its markup as written.
     */
    public function description(): ?string
    {
        foreach ($this->fieldsOf(self::TEXT) as $fields) {
            /** @var mixed $text */
            $text = $fields['description'] ?? null;
            if (is_string($text) && self::words($text)) {
                return $text;
            }
        }

        return null;
    }

    /**
     * The ids of the first media block's images that has any, in their order.
     *
     * @return list<string>
     */
    public function imageIds(): array
    {
        foreach ($this->fieldsOf(self::MEDIA) as $fields) {
            $ids = self::ids(self::array($fields['images'] ?? null));
            if ([] !== $ids) {
                return $ids;
            }
        }

        return [];
    }

    /**
     * @param list<string> $types
     *
     * @return list<array<string, mixed>>
     */
    private function fieldsOf(array $types): array
    {
        $found = [];
        foreach ($this->blocks as $block) {
            if (! in_array($block['type'], $types, strict: true)) {
                continue;
            }

            $found[] = $block['fields'];
        }

        return $found;
    }
}
