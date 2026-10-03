<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\Cli;
use PPTXenigma\Extracts;

final class CliTest extends TestCase
{
    private const SAMPLE = __DIR__ . '/../examples/sample.pptx';

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

    public function test_reports_a_wrong_command(): void
    {
        foreach ([
            [[], 'The file to read is missing.'],
            [[self::SAMPLE, self::SAMPLE], 'Only one file can be read.'],
            [[self::SAMPLE, '--format=xml'], 'The value of --format must be json, html or text.'],
            [[self::SAMPLE, '--duplicates=all'], 'The value of --duplicates must be error, first or last.'],
            [[self::SAMPLE, '--unknown'], 'Unknown option "--unknown".'],
        ] as [$arguments, $message]) {
            [$code, $out, $err] = $this->execute($arguments);

            self::assertSame(2, $code);
            self::assertSame('', $out);
            self::assertStringStartsWith($message, $err);
            self::assertStringContainsString('Usage: pptx-enigma', $err);
        }
    }

    public function test_shows_the_help(): void
    {
        foreach (['--help', '-h'] as $option) {
            [$code, $out] = $this->execute([$option]);

            self::assertSame(0, $code);
            self::assertStringStartsWith('Usage: pptx-enigma <file.pptx>', $out);
        }
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
