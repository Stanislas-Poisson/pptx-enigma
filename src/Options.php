<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * The settings of an extraction. The object is immutable: use the named arguments to change a setting.
 *
 *     new Options(sign: '§', duplicates: Duplicates::KeepLast, htmlTags: ['bold' => 'strong']);
 */
final readonly class Options
{
    /**
     * The HTML tag of each style.
     */
    public const array DEFAULT_HTML_TAGS = [
        'bold'        => 'b',
        'italic'      => 'i',
        'underline'   => 'u',
        'strike'      => 's',
        'superscript' => 'sup',
        'subscript'   => 'sub',
    ];

    /**
     * The sign that opens and closes the voice-overs when no other one is set.
     */
    public const string DEFAULT_SIGN = '¤';

    /**
     * @var array<string, string> the HTML tag of each style, with the default ones for the styles that are left out
     */
    private array $tags;

    /**
     * @param string|null           $sign        the sign that opens a voice-over, "¤" by default; with null, the sign
     *                                           is the text of the first notes, which are then not searched
     * @param Duplicates            $duplicates  what to do when a speaker uses the same reference twice
     * @param array<string, string> $htmlTags    the HTML tag of a style, by style: bold, italic, underline, strike,
     *                                           superscript and subscript
     * @param list<string>          $linkSchemes the schemes of the links that are kept, in lowercase; the other links
     *                                           are written as plain text
     * @param string|null           $endSign     the sign that closes a voice-over; without it, the sign that opens
     *                                           it closes it too
     *
     * @throws InvalidArgumentException when a setting is not valid
     */
    public function __construct(
        public ?string $sign = self::DEFAULT_SIGN,
        public Duplicates $duplicates = Duplicates::Error,
        array $htmlTags = [],
        public array $linkSchemes = ['http', 'https', 'mailto', 'tel'],
        public ?string $endSign = null,
    ) {
        $this->assertSign($sign);
        $this->assertEndSign($endSign);
        $this->assertHtmlTags($htmlTags);
        $this->assertSchemes($linkSchemes);

        $this->tags = [...self::DEFAULT_HTML_TAGS, ...$htmlTags];
    }

    /**
     * The HTML tag of a style: bold, italic, underline, strike, superscript or subscript.
     */
    public function htmlTag(string $style): string
    {
        return $this->tags[$style] ?? '';
    }

    private function assertEndSign(?string $endSign): void
    {
        if (null !== $endSign && 1 !== preg_match('/\S/u', $endSign)) {
            throw new InvalidArgumentException(
                'The closing sign cannot be empty: leave it out to close with the sign that opens.',
            );
        }
    }

    /**
     * @param array<string, string> $htmlTags
     */
    private function assertHtmlTags(array $htmlTags): void
    {
        foreach ($htmlTags as $style => $tag) {
            if (! array_key_exists($style, self::DEFAULT_HTML_TAGS)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a style: use %s.',
                    $style,
                    implode(', ', array_keys(self::DEFAULT_HTML_TAGS)),
                ));
            }

            if (1 !== preg_match('/^[a-z][a-z0-9]*$/', $tag)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a valid HTML tag: use lowercase letters and digits.',
                    $tag,
                ));
            }
        }
    }

    /**
     * @param list<string> $linkSchemes
     */
    private function assertSchemes(array $linkSchemes): void
    {
        foreach ($linkSchemes as $linkScheme) {
            if (1 !== preg_match('/^[a-z][a-z0-9+.\-]*$/', $linkScheme)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not a valid scheme: use lowercase letters, digits, "+", "." and "-".',
                    $linkScheme,
                ));
            }
        }
    }

    private function assertSign(?string $sign): void
    {
        if (null !== $sign && 1 !== preg_match('/\S/u', $sign)) {
            throw new InvalidArgumentException(
                'The sign cannot be empty: leave it out to read it in the first notes.',
            );
        }
    }
}
