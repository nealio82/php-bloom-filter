.DEFAULT_GOAL := help

SHELL := /bin/bash
COMPOSE := docker compose -f docker/docker-compose.yml -f docker/docker-compose-xdebug.override.yml
APP := $(COMPOSE) exec -T php


##@ Setup / common commands

.PHONY: dev
dev: up composer ## Resume development on an existing application

.PHONY: up
up: ## Bring everything up in development mode
	$(COMPOSE) up -d --build --force-recreate

.PHONY: down
down: ## Stop and clean-up the application (remove containers, networks, images, and volumes)
	$(COMPOSE) down -v --remove-orphans

.PHONY: restart
restart: down up ## Restart the application in development mode


# -------------

##@ Code Quality Checks

.PHONY: test
test: ## Runs unit tests
	$(COMPOSE) exec php vendor/bin/phpunit

.PHONY: mutation
mutation: ## Runs mutation tests
	$(COMPOSE) exec php vendor/bin/infection

.PHONY: cs-fix
cs-fix: ## Runs unit tests
	$(COMPOSE) exec php vendor/bin/php-cs-fixer check

# -------------


##@ Running Example

.PHONY: run-example
run-example: ## Runs the provided example application
	$(COMPOSE) exec php php application.php p:d
	@echo "";
	@echo "If the process above completed immediately with no results, you might need to reload the database."
	@echo "It's likely that a test run was executed, which truncates the DB and MySQL doesn't allow to be rolled-back."
	@echo "Run \`make restart && make run-example\` to try again with a fresh database"

# -------------

##@ Utility commands

.PHONY: composer
composer: ## Installs the latest Composer dependencies within running instance
	$(APP) composer install --no-interaction --no-ansi


# -------------

##@ Running Instance

.PHONY: shell
shell: ## Provides shell access to the running PHP container instance
	$(COMPOSE) exec php bash

.PHONY: logs
logs: ## Tails all container logs
	$(COMPOSE) logs -f

.PHONY: ps
ps: ## List all running containers
	$(COMPOSE) ps

# https://www.thapaliya.com/en/writings/well-documented-makefiles/
.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
