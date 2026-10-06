<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The paths inside the archive of a presentation.
 */
final class PackagePath
{
    /**
     * The path of the file that lists the relationships of a file.
     */
    public static function relationshipsOf(string $path): string
    {
        return dirname($path) . '/_rels/' . basename($path) . '.rels';
    }

    /**
     * Resolves a target of a relationship, absolute or relative to a directory of the archive.
     */
    public static function resolve(string $directory, string $target): string
    {
        $segments = str_starts_with($target, '/') ? [] : explode('/', $directory);

        foreach (explode('/', $target) as $segment) {
            $segments = self::add($segments, $segment);
        }

        return implode('/', $segments);
    }

    /**
     * @param list<string> $segments
     *
     * @return list<string>
     */
    private static function add(array $segments, string $segment): array
    {
        if ('..' === $segment) {
            array_pop($segments);

            return $segments;
        }

        return '' === $segment || '.' === $segment ? $segments : [...$segments, $segment];
    }
}
