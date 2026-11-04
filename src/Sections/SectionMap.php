<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

use function array_key_exists;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function sprintf;

/**
 * The <seo> blocks a site's admin.xml declares, by the record type each
 * names with its type attribute. A block without a type, or a second for a
 * type already mapped, is left out and named among the problems.
 *
 * @api
 */
final readonly class SectionMap
{
    private const string BLOCKS = '/*/*/category/*/section/*/extensions/seo';

    /**
     * @param array<string, SeoBlock> $blocks   By record type
     * @param list<string>            $problems What was wrong with each block left out
     */
    public function __construct(
        public array $blocks = [],
        public array $problems = [],
    ) {}

    /**
     * The blocks in $xml, a site's admin.xml. A file that can't be read
     * maps nothing.
     */
    public static function fromAdminXml(string $xml): self
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded   = '' !== $xml && $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return new self(problems: ['The admin.xml could not be read']);
        }

        /** @var DOMNodeList<DOMElement>|false $found The path matches only elements */
        $found    = (new DOMXPath($document))->query(self::BLOCKS);
        $blocks   = [];
        $problems = [];
        foreach (false === $found ? [] : $found as $seo) {
            $block   = SeoBlockReader::read($seo);
            $section = self::path($seo);
            if (null === $block) {
                $problems[] = sprintf('The <seo> block on %s names no type', $section);

                continue;
            }

            if (array_key_exists($block->type, $blocks)) {
                $problems[] = sprintf('The <seo> block on %s maps %s again', $section, $block->type);

                continue;
            }

            $blocks[$block->type] = $block;
        }

        return new self($blocks, $problems);
    }

    /**
     * The path, group.category.section, of the section a block sits in.
     */
    private static function path(DOMElement $seo): string
    {
        $section  = $seo->parentNode?->parentNode;
        $category = $section?->parentNode?->parentNode;
        $group    = $category?->parentNode?->parentNode;

        return "{$group?->nodeName}.{$category?->nodeName}.{$section?->nodeName}";
    }

    /**
     * The block for records of $type, or null when none maps it.
     */
    public function for(string $type): ?SeoBlock
    {
        return $this->blocks[$type] ?? null;
    }
}
