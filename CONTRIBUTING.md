# Contributing

Thank you for helping. PPTX-Enigma is a PHP extractor of the voice-over texts of a PowerPoint file.

---

## Development workflow

| Step | Command / Action | Description |
| :--- | :--- | :--- |
| **1. Issue** | Open or pick an issue | One branch and one pull request per issue. |
| **2. Branch** | `git checkout -b feature/#TICKET-name develop` | Create a branch from `develop`. |
| **3. Code** | *(your IDE)* | Keep the change small, and write its test. |
| **4. Check** | `make quality` | Run the whole quality gate: Pint, PHPStan, Rector, PHP Insights and PHPUnit. |
| **5. Commit** | `git commit -m "type(scope): #TICKET subject"` | Use the [Conventional Commits][conventional-commits] format, in English, 72 characters at most. |
| **6. Push** | `git push origin feature/#TICKET-name` | Push and open a pull request to `develop`. |

A pull request needs a review and is merged with a merge commit.

---

## Checks

The Composer scripts are the source of truth, and the `Makefile` is a short way to call them. `make help` lists the commands.

| Command | Tool | Description |
| :--- | :--- | :--- |
| `make install` | Composer | Install the development tools. |
| `make hooks` | Git | Activate the hooks of php-dev-tools: the commit message, `quality:fast` before a commit, `quality` before a push. |
| `make cs` | Pint | Check the code style. `make cs-fix` fixes it. |
| `make analyse` | PHPStan | Static analysis at the maximum level with the strict rules, without a baseline. |
| `make rector` | Rector | Check what Rector would change. `make rector-fix` applies it. |
| `make insights` | PHP Insights | The four scores (code, complexity, architecture, style) must be 100 %. |
| `make markdown` | markdownlint | Lint the Markdown files (needs Node.js). |
| `make test` | PHPUnit | Run the tests. `make coverage` shows the coverage, which must stay at 100 % for `src/`. |
| `make quality` | All | The whole gate, without the Markdown. `make quality-fix` fixes what can be fixed. |

The tests build their presentations on the fly (`tests/PptxBuilder.php`) and use `examples/sample.pptx`, which is fictional. Never add a file with a private or confidential content.

The rules come from [php-dev-tools](https://github.com/Stanislas-Poisson/php-dev-tools), which the files of this repository extend: `pint.json`, `phpstan.neon.dist`, `rector.php`, `phpinsights.php` and `.markdownlint.json` only hold what is specific to PPTX-Enigma. No file is excluded to hide an error.

The `ci` check runs the same commands on PHP 8.3 and 8.4, and must pass before a change reaches `develop` or `main`.

---

## Releasing

Reserved to the maintainer. Tags are plain `X.Y.Z`, signed, and made on `main` only.

1. Merge `develop` into `main` with a pull request, and wait for the CI of `main`.
2. Tag the merge commit and push the tag:

   ```bash
   git checkout main && git pull
   git tag -s 1.0.0 -m "1.0.0"
   git push origin 1.0.0
   ```

3. The `Release` workflow checks that the tag is on `main` and that the CI passed on that commit, then creates the GitHub release. Its notes list the merged pull requests by label and give the `composer require` line.
4. Packagist reads the new tag by itself, once the package is submitted and its GitHub hook is active. The last step of the workflow warns when it does not list the version.

---

## Language

Code, comments, commits, issues and pull requests are in English.

[conventional-commits]: https://www.conventionalcommits.org/
