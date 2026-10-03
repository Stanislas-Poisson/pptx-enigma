.DEFAULT_GOAL := help

.PHONY: help
help: ## List the commands
	@grep -hE '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-14s %s\n", $$1, $$2}'

.PHONY: install
install: ## Install the dependencies
	composer install --no-interaction --prefer-dist

.PHONY: hooks
hooks: ## Activate the Git hooks of .githooks
	git config core.hooksPath .githooks

.PHONY: test
test: ## Run the tests
	composer test

.PHONY: coverage
coverage: ## Run the tests with the coverage of src/
	composer test:coverage

.PHONY: cs
cs: ## Check the code style with Pint
	composer cs

.PHONY: cs-fix
cs-fix: ## Fix the code style with Pint
	composer cs:fix

.PHONY: analyse
analyse: ## Run PHPStan at the maximum level
	composer analyse

.PHONY: rector
rector: ## Check what Rector would change
	composer rector

.PHONY: rector-fix
rector-fix: ## Apply the changes of Rector
	composer rector:fix

.PHONY: insights
insights: ## Run PHP Insights, which must give 100 % everywhere
	composer insights

.PHONY: markdown
markdown: ## Lint the Markdown files
	composer markdown

.PHONY: quality-fast
quality-fast: ## Check the style and run the analysis (the pre-commit hook)
	composer quality:fast

.PHONY: quality
quality: ## Run the whole quality gate (the pre-push hook)
	composer quality

.PHONY: quality-fix
quality-fix: ## Fix what can be fixed: Rector, then Pint
	composer quality:fix
