.DEFAULT_GOAL := help

.PHONY: install
install: ## Install the dependencies
	composer install --no-interaction --prefer-dist

-include vendor/stanislas-poisson/php-dev-tools/Makefile.inc
