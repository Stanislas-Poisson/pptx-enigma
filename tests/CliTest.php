<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\Cli;
use PPTXenigma\Extracts;

final class CliTest extends TestCase
{
    private const string SAMPLE = __DIR__ . '/../examples/sample.pptx';

    public function test_reads_the_sign_in_the_first_notes_when_asked(): void
    {
        $path = PptxBuilder::build([
            ['file' => 1, 'notes' => ['§']],
            ['file' => 2, 'notes' => ['§ V (A) r1', 'text', '§']],
        ]);

        try {
            [$none]         = $this->execute([$path]);
            [, $out]        = $this->execute([$path, '--sign-in-notes']);
            [$both, , $err] = $this->execute([$path, '--sign-in-notes', '--sign=§']);
        }
        finally {
            unlink($path);
        }

        self::assertSame(0, $none);
        self::assertSame(['A' => ['r1' => '<p>text</p>']], json_decode($out, true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(2, $both);
        self::assertStringStartsWith('Use --sign or --sign-in-notes, not both.', $err);
    }

    public function test_reports_a_directory_that_cannot_be_written(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'pptx');

        set_error_handler(static fn (): bool => true);

        try {
            [$code, , $err] = $this->execute([self::SAMPLE, '--split=' . $file . '/sub']);
        }
        finally {
            restore_error_handler();
            unlink($file);
        }

        self::assertSame(1, $code);
        self::assertStringContainsString('cannot be created', $err);
    }

    public function test_reports_a_file_that_cannot_be_written(): void
    {
        $directory = sys_get_temp_dir() . '/pptx-enigma-' . bin2hex(random_bytes(4));
        mkdir($directory . '/guide.txt', 0o775, true);

        set_error_handler(static fn (): bool => true);

        try {
            [$code, , $err] = $this->execute([self::SAMPLE, '--split=' . $directory]);
        }
        finally {
            restore_error_handler();
            rmdir($directory . '/guide.txt');
            rmdir($directory);
        }

        self::assertSame(1, $code);
        self::assertStringContainsString('cannot be written', $err);
    }

    public function test_reports_a_speaker_that_does_not_exist(): void
    {
        [$code, $out, $err] = $this->execute([self::SAMPLE, '--speaker=Nobody']);

        self::assertSame(1, $code);
        self::assertSame('', $out);
        self::assertStringContainsString('There is no speaker "Nobody"', $err);
    }

    public function test_reports_a_wrong_command(): void
    {
        foreach ([
            [[], 'The file to read is missing.'],
            [[self::SAMPLE, self::SAMPLE], 'Only one file can be read.'],
            [[self::SAMPLE, '--format=xml'], 'The value of --format must be json, html, text or pdf.'],
            [[self::SAMPLE, '--duplicates=all'], 'The value of --duplicates must be one of: error, first, last.'],
            [[self::SAMPLE, '--unknown'], 'Unknown option "--unknown".'],
            [[self::SAMPLE, '--unknown=1'], 'Unknown option "--unknown".'],
            [[self::SAMPLE, '-x'], 'Unknown option "-x".'],
            [[self::SAMPLE, '--format=pdf'], 'A PDF is written in a directory: use --split=DIR.'],
            [[self::SAMPLE, '--speaker=Guide', '--split=/tmp/x'], 'Use --speaker or --split, not both.'],
            [[self::SAMPLE, '--split='], 'The value of --split is missing: it is a directory.'],
            [[self::SAMPLE, '--speaker=Guide', '--format=json'], 'A script is written as text, html or pdf.'],
            [[self::SAMPLE, '--layout=above'], '--layout needs --speaker or --split.'],
            [[self::SAMPLE, '--no-slides'], '--no-slides needs --speaker or --split.'],
            [[self::SAMPLE, '--speaker=Guide', '--layout=left'], 'The value of --layout must be one of: above, below, inline, none.'],
            [[self::SAMPLE, '--speaker=Guide', '--emphasis=bold'], 'The value of --emphasis must be one of: marks, none, upper.'],
        ] as [$arguments, $message]) {
            [$code, $out, $err] = $this->execute($arguments);

            self::assertSame(2, $code);
            self::assertSame('', $out);
            self::assertStringStartsWith($message, $err);
            self::assertStringContainsString('Usage: pptx-enigma', $err);
        }
    }

    public function test_reports_an_extraction_error(): void
    {
        [$code, $out, $err] = $this->execute([self::SAMPLE, '--sign=§']);

        self::assertSame(0, $code, 'A sign that is not in the notes finds no voice-over, and it is not an error.');
        self::assertSame("[]\n", $out);
        self::assertSame('', $err);

        [$code, , $err] = $this->execute([__DIR__ . '/missing.pptx']);

        self::assertSame(1, $code);
        self::assertStringContainsString('does not exist or is not readable', $err);
    }

    public function test_shows_the_help(): void
    {
        foreach (['--help', '-h'] as $option) {
            [$code, $out] = $this->execute([$option]);

            self::assertSame(0, $code);
            self::assertStringStartsWith('Usage: pptx-enigma <file.pptx>', $out);
        }
    }

    public function test_uses_a_closing_sign(): void
    {
        $path = PptxBuilder::build([['file' => 1, 'notes' => ['<< V (A) r1', 'text', '>>', 'outside']]]);

        try {
            [$code, $out] = $this->execute([$path, '--sign=<<', '--end-sign=>>']);
        }
        finally {
            unlink($path);
        }

        self::assertSame(0, $code);
        self::assertSame(['A' => ['r1' => '<p>text</p>']], json_decode($out, true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_uses_the_sign_and_the_duplicates_options(): void
    {
        [$code, $out] = $this->execute([self::SAMPLE, '--sign=¤', '--duplicates=last', '--format=json']);

        self::assertSame(0, $code);
        self::assertSame((new Extracts(self::SAMPLE))->extract()->toArray(), json_decode($out, true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(0, $this->execute([self::SAMPLE, '--duplicates=first'])[0]);
        self::assertSame(0, $this->execute([self::SAMPLE, '--duplicates=error'])[0]);
    }

    public function test_writes_html_and_text(): void
    {
        [, $html] = $this->execute([self::SAMPLE, '--format=html']);
        [, $text] = $this->execute(['--format=text', self::SAMPLE]);

        self::assertStringStartsWith('<h2>Guide</h2>', $html);
        self::assertStringStartsWith("Guide\n=====\n", $text);
    }

    public function test_writes_json_by_default(): void
    {
        [$code, $out, $err] = $this->execute([self::SAMPLE]);

        self::assertSame(0, $code);
        self::assertSame('', $err);
        self::assertStringEndsWith("}\n", $out);
        self::assertSame((new Extracts(self::SAMPLE))->extract()->toArray(), json_decode($out, true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_writes_one_file_per_speaker(): void
    {
        $directory = sys_get_temp_dir() . '/pptx-enigma-' . bin2hex(random_bytes(4)) . '/sub';

        [$code, $out, $err] = $this->execute([self::SAMPLE, '--split=' . $directory]);

        self::assertSame(0, $code);
        self::assertSame('', $err);
        self::assertSame($directory . '/guide.txt' . "\n" . $directory . '/narrator.txt' . "\n", $out);
        self::assertStringStartsWith("Guide\n=====\n", (string) file_get_contents($directory . '/guide.txt'));

        $this->execute([self::SAMPLE, '--split=' . $directory . '/', '--format=html']);
        $this->execute([self::SAMPLE, '--split=' . $directory, '--format=pdf']);

        self::assertStringStartsWith('<!DOCTYPE html>', (string) file_get_contents($directory . '/narrator.html'));
        self::assertStringStartsWith('%PDF-', (string) file_get_contents($directory . '/narrator.pdf'));

        $files = glob($directory . '/*');
        array_map(unlink(...), false === $files ? [] : $files);
        rmdir($directory);
        rmdir(dirname($directory));
    }

    public function test_writes_the_script_of_one_speaker(): void
    {
        [$code, $out, $err] = $this->execute([self::SAMPLE, '--speaker=Narrator', '--layout=inline', '--no-slides', '--no-counts']);

        self::assertSame(0, $code);
        self::assertSame('', $err);
        self::assertStringStartsWith("Narrator\n========\n\n[w01_intro] Welcome to this \n*fictional* presentation", $out);

        [, $html] = $this->execute([self::SAMPLE, '--speaker=Guide', '--format=html', '--emphasis=none']);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<h1>Guide</h1>', $html);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{int, string, string} the code, the output and the error output
     */
    private function execute(array $arguments): array
    {
        $out  = '';
        $err  = '';
        $code = (new Cli())->run(
            $arguments,
            static function (string $text) use (&$out): void {
                $out .= $text;
            },
            static function (string $text) use (&$err): void {
                $err .= $text;
            },
        );

        return [$code, $out, $err];
    }
}
