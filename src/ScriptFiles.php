<?php

declare(strict_types=1);

namespace PPTXenigma;

use RuntimeException;

/**
 * Writes the script of each speaker in a file of a directory, named after the speaker.
 */
final readonly class ScriptFiles
{
    private const array ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
        'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'œ' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y',
        'ÿ' => 'y', 'ß' => 'ss',
    ];

    /**
     * @param string $format "text", "html" or "pdf"
     *
     * @return list<string> the paths of the files that were written
     *
     * @throws MissingDependencyException when a PDF is asked and dompdf is not installed
     * @throws RuntimeException           when the directory or a file cannot be written
     */
    public function write(
        VoiceOvers $voiceOvers,
        ScriptOptions $scriptOptions,
        string $directory,
        string $format,
    ): array {
        $this->createDirectory($directory);

        $paths = [];
        $taken = [];

        foreach ($voiceOvers->scripts($scriptOptions) as $speaker => $script) {
            $path = $this->path($directory, $this->name($speaker, $taken), $format);

            $this->save($path, $this->content($script, $format));

            $paths[] = $path;
        }

        return $paths;
    }

    private function content(Script $script, string $format): string
    {
        return match ($format) {
            'html'  => $script->toHtml(),
            'pdf'   => $script->toPdf(),
            default => $script->toText(),
        };
    }

    private function createDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0o775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('The directory "%s" cannot be created.', $directory));
        }
    }

    /**
     * A file name made of letters, digits and dashes, which no other speaker has.
     *
     * @param list<string> $taken the names already given
     */
    private function name(string $speaker, array &$taken): string
    {
        $base   = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtr(mb_strtolower($speaker), self::ACCENTS)), '-');
        $base   = '' === $base ? 'speaker' : $base;

        $name   = $base;
        $number = 2;

        while (in_array($name, $taken, true)) {
            $name = $base . '-' . $number;
            $number++;
        }

        $taken[] = $name;

        return $name;
    }

    private function path(string $directory, string $name, string $format): string
    {
        return rtrim($directory, '/') . '/' . $name . '.' . ('text' === $format ? 'txt' : $format);
    }

    private function save(string $path, string $content): void
    {
        if (false === file_put_contents($path, $content)) {
            throw new RuntimeException(sprintf('The file "%s" cannot be written.', $path));
        }
    }
}
