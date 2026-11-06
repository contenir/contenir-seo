<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use DOMElement;

use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function trim;

/**
 * Reads a <seo> element into its block: each child names a role and lists
 * the columns it reads, separated by commas; an alt attribute lists the
 * columns for the role's alt text, required="true" marks a role a record
 * must fill, and <advanced> names the column holding a record's overrides. mode="manual" on <seo>
 * stops a page taking a description or image from the record's Section field, and content="false" on a role
 * stops that role alone taking it.
 *
 * @api
 */
final class SeoBlockReader
{
    /**
     * The block $seo declares, or null when it names no type.
     */
    public static function read(DOMElement $seo): ?SeoBlock
    {
        $type = trim($seo->getAttribute('type'));
        if ('' === $type) {
            return null;
        }

        $roles    = SeoBlock::CONVENTIONS;
        $required = [];
        $advanced = null;
        $content  = SeoBlock::CONTENT_ROLES;
        foreach ($seo->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $role = $child->localName ?? $child->nodeName;
            if ('advanced' === $role) {
                $advanced = self::columns($child->textContent)[0] ?? null;

                continue;
            }

            $roles[$role] = self::columns($child->textContent);
            if ($child->hasAttribute('alt')) {
                $roles["{$role}@alt"] = self::columns($child->getAttribute('alt'));
            }

            if ('true' === $child->getAttribute('required')) {
                $required[] = $role;
            }

            if ('false' === $child->getAttribute('content')) {
                $content = array_diff($content, [$role]);
            }
        }

        $schema = trim($seo->getAttribute('schema'));

        return new SeoBlock(
            $type,
            '' === $schema ? null : $schema,
            $roles,
            $required,
            $advanced,
            'manual' !== trim($seo->getAttribute('mode')),
            array_values($content),
        );
    }

    /**
     * The items of a comma-separated list of columns.
     *
     * @return list<string>
     */
    private static function columns(string $list): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode(',', $list)),
            static fn(string $column): bool => '' !== $column,
        ));
    }
}
