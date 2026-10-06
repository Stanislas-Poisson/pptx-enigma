<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The relationships of a file of the archive: the files and the links that it refers to.
 */
final readonly class Relationships
{
    private const string NAMESPACE_URI = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @param array<string, array{string, string}> $relationships the type and the target of each relationship, by id
     */
    private function __construct(private array $relationships) {}

    /**
     * The relationships that the file at the given path of the archive lists, none if it lists nothing.
     */
    public static function of(Archive $archive, string $path): self
    {
        $document      = $archive->load(PackagePath::relationshipsOf($path));
        $relationships = [];

        foreach ($document?->getElementsByTagNameNS(self::NAMESPACE_URI, 'Relationship') ?? [] as $element) {
            $relationships[$element->getAttribute('Id')] = [
                $element->getAttribute('Type'),
                $element->getAttribute('Target'),
            ];
        }

        return new self($relationships);
    }

    public function firstTarget(string $type): ?string
    {
        return array_values($this->targets($type))[0] ?? null;
    }

    /**
     * @param string|null $type the end of the type of the relationships to keep, such as "/slide"; null keeps them all
     *
     * @return array<string, string> the target of each relationship, by id
     */
    public function targets(?string $type = null): array
    {
        $targets = [];

        foreach ($this->relationships as $id => [$relationshipType, $target]) {
            if (null === $type || str_ends_with($relationshipType, $type)) {
                $targets[$id] = $target;
            }
        }

        return $targets;
    }
}
