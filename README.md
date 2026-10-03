# ![PPTX-Enigma](https://raw.githubusercontent.com/Stanislas-Poisson/pptx-enigma/develop/static/img/logo.jpg)

PPTX-Enigma extracts the voice-over texts written in the speaker notes of a PowerPoint (`.pptx`) file. The texts are grouped by speaker and by reference, and the formatting of the notes (bold, italic, underline, strikethrough, superscript, subscript, lists) is converted to HTML.

> **Status: proof of concept.** The extractor works on well-formed notes, but it depends on a few conventions, it has known bugs, and it is not a package yet: no Composer, no test, no configuration. Do not use it on files you do not trust. See [Known issues](#known-issues) and [Roadmap](#roadmap).

## Requirements

PHP with the `zip` and `fileinfo` extensions. The code has been run on PHP 8.4, where it raises a deprecation notice (see the known issues). There is no other dependency.

## The format of the notes

The extractor reads the notes of the slides. It expects:

1. A **sign**, a short marker that delimits the voice-overs, for example `¤`. The first notes page (`notesSlide1.xml`) is only read to find the sign: it holds only the sign, and it is not searched for voice-overs.
2. A **marker line** that starts with the sign, then the speaker between parentheses, then the reference: `¤ VOICE OVER (Narrator) w01_intro`. The words before the parentheses are free text.
3. **An empty paragraph**, then the paragraphs of the text.
4. A **closing line** that starts with the sign: `¤ VOICE OVER END`.

A speaker can have several voice-overs in the same notes, but a reference can be used only once per speaker.

## Usage

There is no Composer package yet. `examples/sample.pptx` is a small fictional presentation that follows the format above:

```php
<?php

require 'includes/PPTXenigma/Extracts.php';

$extractor = new PPTXenigma\Extracts([
    'fileName' => 'sample',            // the file name, without ".pptx"
    'location' => __DIR__ . '/examples/',    // the directory of the file, with a trailing slash
    'dirTmp'   => __DIR__ . '/tmp/',         // an existing, writable directory, with a trailing slash
]);

print_r($extractor->extractFiles()->getVoiceOver());
```

Output:

```text
Array
(
    [Guide] => Array
        (
            [ w02_guide] => <p>Hello, I am the guide.</p>
            [ w03_goodbye] => <p>That is all, thank you.</p>
        )

    [Narrator] => Array
        (
            [ w01_intro] => <p>Welcome to this </p><p><b>fictional</b> presentation, written only to test the extractor.</p><p>It has <i>two</i> voices.</p>
            [ w02_list] => <p>Three things to remember:</p><ul><li>Notes are read slide by slide.</li><li>Voices are grouped by speaker.</li><li>References identify each voice-over.</li></ul>
        )

)
```

The speakers are sorted by name. The references keep the space that follows the closing parenthesis.

## Known issues

These problems were found by reading the code and by running it on PHP 8.4.

- **Slide order.** The notes are read in alphabetical order, so `notesSlide10.xml` comes before `notesSlide2.xml`. With more than nine notes pages the order, and the page used to find the sign, are wrong.
- **First paragraph.** The first paragraph after a marker line is dropped, unless it is preceded by an empty paragraph.
- **Lists.** A paragraph with a bullet definition (`a:buFont`) is always handled as a list item, even when the bullet is `buNone`. Notes exported from some tools therefore come out as lists only.
- **Errors.** Several failures end with `die()` instead of an exception, the `catch ( \Exceptions $e )` clauses never catch anything, and the result of opening the archive is not checked.
- **Temporary files** are not removed from the temporary directory.
- **PHP 8.2 and later** report a deprecation: `$nbrSlides` is not declared.
- **XML** is read with regular expressions, and the sign is not escaped in them.
- **`www/` and `includes/Core/`** hold the original web demo. They contain a server path and URLs, start the debug mode and a session, print the text without escaping it, and `www/index.php` refers to example files that were removed. They cannot run as they are, and the extractor does not need them.

## Roadmap

1. Fix the known bugs and clean the application ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)).
2. Add the missing features ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)).
3. Turn PPTX-Enigma into a Composer package with a configuration ([#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)).

## License

[MIT](LICENSE). Copyright (c) 2017 Stanislas Poisson.
