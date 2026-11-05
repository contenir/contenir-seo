<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use function is_scalar;
use function preg_match;
use function trim;

use const PREG_UNMATCHED_AS_NULL;

/**
 * A value a <seo> block reads from a record the page's record links to rather
 * than from a column of its own: `hero.path` reads the path column of the
 * record its hero relation points at, and `assets[type=hero].path` reads the
 * path of the first asset whose type column is "hero". The relation goes by
 * the name the site's entity gives it; the columns go by their names in the
 * database, as everywhere else in a block.
 *
 * @api
 */
final readonly class LinkedValue
{
    private const string PATTERN = '/^([a-zA-Z_]\w*)(?:\[([a-z_]\w*)=([^\],\s]+)\])?\.([a-z_]\w*)$/';

    public function __construct(
        public string $relation,
        public string $column,
        public ?string $field = null,
        public ?string $value = null,
    ) {}

    /**
     * The links among $sources, by how each is written.
     *
     * @param list<string> $sources
     *
     * @return array<string, self>
     */
    public static function among(array $sources): array
    {
        $links = [];
        foreach ($sources as $source) {
            $link = self::parse($source);
            if (null !== $link) {
                $links[$source] = $link;
            }
        }

        return $links;
    }

    /**
     * Whether any of $sources is a link, so its value can't be told from the record's own columns.
     *
     * @param list<string> $sources
     */
    public static function any(array $sources): bool
    {
        return [] !== self::among($sources);
    }

    /**
     * What $source links to, or null when it isn't a link, as a column's name isn't.
     */
    public static function parse(string $source): ?self
    {
        $parts = [];
        if (1 !== preg_match(self::PATTERN, $source, $parts, PREG_UNMATCHED_AS_NULL)) {
            return null;
        }

        /** @var array{string, string, ?string, ?string, string} $parts */
        return new self($parts[1], $parts[4], $parts[2], $parts[3]);
    }

    /**
     * The column's first value that isn't empty among the linked records' rows that match.
     *
     * @param iterable<array<string, mixed>> $rows The linked records' columns by name, in their order
     */
    public function from(iterable $rows): ?string
    {
        foreach ($rows as $row) {
            /** @var mixed $value */
            $value = $row[$this->column] ?? null;
            if ($this->matches($row) && is_scalar($value) && '' !== trim((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function matches(array $row): bool
    {
        if (null === $this->field) {
            return true;
        }

        /** @var mixed $value */
        $value = $row[$this->field] ?? null;

        return is_scalar($value) && (string) $value === $this->value;
    }
}
