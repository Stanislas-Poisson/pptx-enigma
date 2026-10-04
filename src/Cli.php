<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;
use RuntimeException;

/**
 * The command line: reads a presentation and writes its voice-overs.
 */
final class Cli
{
    private const string USAGE = <<<'TXT'
        Usage: pptx-enigma <file.pptx> [options]

        Options:
          --format=json|html|text|pdf how to write the voice-overs (default: json; text for a script)
          --sign=SIGN                 the sign that opens a voice-over (default: the text of the first notes)
          --end-sign=SIGN             the sign that closes a voice-over (default: the sign that opens it)
          --duplicates=error|first|last
                                      what to do when a speaker uses a reference twice (default: error)
          --speaker=NAME              write the script of one speaker (text or html) instead
          --split=DIR                 write one script per speaker in a directory (text, html or pdf)
          --layout=above|below|inline|none
                                      where the reference is in a script (default: above)
          --emphasis=marks|upper|none how the styles are written in a text script (default: marks)
          --no-slides                 do not write the slide of each voice-over in a script
          --no-counts                 do not write the number of voice-overs and words in a script
          -h, --help                  show this help

        TXT;

    /**
     * @param list<string>          $arguments the arguments, without the name of the script
     * @param callable(string):void $out       writes on the standard output
     * @param callable(string):void $err       writes on the standard error
     *
     * @return int 0 on success, 1 when the extraction fails, 2 when the command is not valid
     */
    public function run(array $arguments, callable $out, callable $err): int
    {
        try {
            $parsed = CliArguments::parse($arguments);
        }
        catch (InvalidArgumentException $invalidArgumentException) {
            $err($invalidArgumentException->getMessage() . "\n\n" . self::USAGE);

            return 2;
        }

        if ($parsed->help) {
            $out(self::USAGE);

            return 0;
        }

        return $this->extract($parsed, $out, $err);
    }

    /**
     * @param callable(string):void $out
     * @param callable(string):void $err
     */
    private function extract(CliArguments $cliArguments, callable $out, callable $err): int
    {
        $options = new Options(
            sign: $cliArguments->sign,
            duplicates: $cliArguments->duplicates,
            endSign: $cliArguments->endSign,
        );

        try {
            $voiceOvers = (new Extracts($cliArguments->file, $options))->extract();
        }
        catch (InvalidArgumentException|ExtractionException $exception) {
            $err($exception->getMessage() . "\n");

            return 1;
        }

        try {
            $this->write($cliArguments, $voiceOvers, $out);
        }
        catch (InvalidArgumentException|MissingDependencyException|RuntimeException $exception) {
            $err($exception->getMessage() . "\n");

            return 1;
        }

        return 0;
    }

    /**
     * @param callable(string):void $out
     */
    private function write(CliArguments $cliArguments, VoiceOvers $voiceOvers, callable $out): void
    {
        if (null !== $cliArguments->split) {
            $scriptFiles = new ScriptFiles();
            $paths       = $scriptFiles->write(
                $voiceOvers,
                $cliArguments->scriptOptions,
                $cliArguments->split,
                $cliArguments->format,
            );

            $out(implode("\n", $paths) . "\n");

            return;
        }

        if (null !== $cliArguments->speaker) {
            $script = $voiceOvers->script($cliArguments->speaker, $cliArguments->scriptOptions);

            $out('html' === $cliArguments->format ? $script->toHtml() : $script->toText());

            return;
        }

        $out(match ($cliArguments->format) {
            'html'  => $voiceOvers->toHtml(),
            'text'  => $voiceOvers->toText(),
            default => $voiceOvers->toJson() . "\n",
        });
    }
}
