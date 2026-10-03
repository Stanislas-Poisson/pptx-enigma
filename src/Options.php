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
     * @var array<string, string> the HTML tag of each style, with the default ones for the styles that are left out
     */
    public array $htmlTags;

    /**
     * @param string|null           $sign        the sign that delimits the voice-overs; without it, the sign is the text
     *                                           of the first notes, which are then not searched for voice-overs
     * @param Duplicates            $duplicates  what to do when a speaker uses the same reference twice
     * @param array<string, string> $htmlTags    the HTML tag of a style, by style: bold, italic, underline, strike,
     *                                           superscript and subscript
     * @param list<string>          $linkSchemes the schemes of the links that are kept, in lowercase; the other links are
     *                                           written as plain text
     *
     * @throws InvalidArgumentException when a setting is not valid
     */
    public function __construct(
        public ?string $sign = null,
        public Duplicates $duplicates = Duplicates::Error,
        array $htmlTags = [],
        public array $linkSchemes = ['http', 'https', 'mailto', 'tel'],
    ) {
        if (null !== $sign && 1 !== preg_match('/\S/u', $sign)) {
            throw new InvalidArgumentException('The sign cannot be empty: leave it out to read it in the first notes.');
        }

        foreach ($htmlTags as $style => $tag) {
            if (! array_key_exists($style, self::DEFAULT_HTML_TAGS)) {
                throw new InvalidArgumentException(sprintf('"%s" is not a style: use %s.', $style, implode(', ', array_keys(self::DEFAULT_HTML_TAGS))));
            }

            if (1 !== preg_match('/^[a-z][a-z0-9]*$/', $tag)) {
                throw new InvalidArgumentException(sprintf('"%s" is not a valid HTML tag: use lowercase letters and digits.', $tag));
            }
        }

        foreach ($linkSchemes as $linkScheme) {
            if (1 !== preg_match('/^[a-z][a-z0-9+.\-]*$/', $linkScheme)) {
                throw new InvalidArgumentException(sprintf('"%s" is not a valid scheme: use lowercase letters, digits, "+", "." and "-".', $linkScheme));
            }
        }

        $this->htmlTags = [...self::DEFAULT_HTML_TAGS, ...$htmlTags];
    }
}
