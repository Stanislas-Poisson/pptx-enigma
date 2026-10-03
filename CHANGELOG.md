# Changelog

All notable changes are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/), and the project will follow [Semantic Versioning](https://semver.org/) once it has a first release. There is no release yet.

## Unreleased

### Added

- The line breaks and the hyperlinks of the notes, and an error for a voice-over that is not closed ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)).
- The `Options` object (the sign, the duplicates, the HTML tags, the link schemes), the `VoiceOvers` result with the HTML, plain text, JSON and array formats, and the `bin/pptx-enigma` command ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4), [#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)).

- The quality tools of the other zairakai projects: Pint, PHPStan with the strict rules, Rector, PHP Insights at 100 %, markdownlint, a `Makefile` and Git hooks ([#16](https://github.com/Stanislas-Poisson/pptx-enigma/issues/16)).

### Changed

- The extractor is rewritten in small classes under `src/` (PSR-4, `PPTXenigma\`): the archive is read in memory, the slides are read in the order of the presentation, and the XML is read with DOM ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)).
- `extract()` returns a `VoiceOvers` object. The sign is an option, and `setSign()`, `getSign()` and `getVoiceOver()` are gone.
- The web demo, the custom autoloader and the server configuration are removed.
- The extractor is split into small classes (the archive, the relationships, the paths, the notes, the voice-over reader and its index) to keep PHP Insights at 100 %. `Options` has a method `htmlTag()` instead of a public property.

### Fixed

- The first paragraph of a voice-over is no longer dropped, `buNone` is a paragraph, and the errors are exceptions ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)).
