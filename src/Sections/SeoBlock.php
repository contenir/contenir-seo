<?php

declare(strict_types=1);

namespace Contenir\Seo\Sections;

use function array_merge;
use function array_unique;
use function array_values;
use function in_array;
use function is_scalar;
use function trim;

/**
 * One section's <seo> block: the record type it describes, the schema.org
 * type of its pages, and the columns each SEO role reads, first non-empty
 * value winning. A role the block leaves out takes the conventions most
 * sites follow: meta_title then title, meta_description then description,
 * and the title as the image's alt text.
 *
 * @api
 */
final readonly class SeoBlock
{
    /** The columns each role reads when a block names none. */
    public const array CONVENTIONS = [
        'title'       => ['meta_title', 'title'],
        'description' => ['meta_description', 'description'],
        'image'       => [],
        'image@alt'   => ['title'],
    ];

    /**
     * @param array<string, list<string>> $roles    The columns each role reads, by role
     * @param list<string>                $required The roles a record must fill
     * @param string|null                 $advanced The column holding a record's overrides as JSON
     */
    public function __construct(
        public string $type,
        public ?string $schema = null,
        public array $roles = self::CONVENTIONS,
        public array $required = [],
        public ?string $advanced = null,
    ) {}

    /**
     * Every column the block reads, its advanced column included.
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return array_values(array_unique([
            ...(null === $this->advanced ? [] : [$this->advanced]),
            ...array_merge(...array_values($this->roles)),
        ]));
    }

    /**
     * What the block makes of $row, a record's columns by name.
     *
     * @param array<string, mixed> $row
     */
    public function page(array $row): MappedPage
    {
        $advanced = Overrides::from(null === $this->advanced ? null : $row[$this->advanced] ?? null);

        return new MappedPage(
            title: $this->first('title', $row),
            description: $this->first('description', $row),
            image: $this->first('image', $row),
            imageAlt: $this->first('image@alt', $row),
            canonical: $advanced['canonical'] ?? null,
            robots: $advanced['robots'] ?? null,
            schema: $advanced['schema'] ?? $this->schema,
        );
    }

    /**
     * Whether $role is one the block says a record must fill.
     */
    public function requires(string $role): bool
    {
        return in_array($role, $this->required, strict: true);
    }

    /**
     * The first value of $role's columns in $row that isn't empty.
     *
     * @param array<string, mixed> $row
     */
    private function first(string $role, array $row): ?string
    {
        foreach ($this->roles[$role] ?? [] as $column) {
            /** @var mixed $value */
            $value = $row[$column] ?? null;
            if (is_scalar($value) && '' !== trim((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
