.DEFAULT_GOAL := help

# --- Variables ---

# Directory containing this Makefile
ROOT_DIR := $(dir $(abspath $(lastword $(MAKEFILE_LIST))))

# Required secrets (override via environment or .env.local)
APP_SECRET ?= $(shell test -f .env.local && grep -E '^APP_SECRET=' .env.local | cut -d '=' -f2-)
CADDY_MERCURE_JWT_SECRET ?= $(shell test -f .env.local && grep -E '^CADDY_MERCURE_JWT_SECRET=' .env.local | cut -d '=' -f2-)
POSTGRES_PASSWORD ?= $(shell test -f .env.local && grep -E '^POSTGRES_PASSWORD=' .env.local | cut -d '=' -f2-)

# Docker Compose files used for production behind Traefik
COMPOSE_FILES := -f compose.yaml -f compose.prod.yaml -f compose.traefik.yaml

# Optional network name for the external Traefik network
TRAEFIK_NETWORK_NAME ?= proxy

.PHONY: help prod prod-build prod-down prod-logs prod-pull prod-clean check-secrets

## help: Show this help message
help:
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## prod: Deploy (or update) the production stack behind Traefik
prod: check-secrets
	docker compose $(COMPOSE_FILES) build --pull --no-cache
	TRAEFIK_NETWORK_NAME=$(TRAEFIK_NETWORK_NAME) \
	APP_SECRET=$(APP_SECRET) \
	CADDY_MERCURE_JWT_SECRET=$(CADDY_MERCURE_JWT_SECRET) \
	POSTGRES_PASSWORD=$(POSTGRES_PASSWORD) \
	docker compose $(COMPOSE_FILES) up --build -d --remove-orphans

## prod-build: Build the production images without starting the stack
prod-build: check-secrets
	APP_SECRET=$(APP_SECRET) \
	CADDY_MERCURE_JWT_SECRET=$(CADDY_MERCURE_JWT_SECRET) \
	POSTGRES_PASSWORD=$(POSTGRES_PASSWORD) \
	docker compose $(COMPOSE_FILES) build --pull --no-cache

## prod-down: Stop the production stack
prod-down:
	docker compose $(COMPOSE_FILES) down --remove-orphans

## prod-logs: Follow the production stack logs
prod-logs:
	docker compose $(COMPOSE_FILES) logs -f

## prod-pull: Pull the latest base images
prod-pull:
	docker compose $(COMPOSE_FILES) pull

## prod-clean: Stop the stack and remove volumes (WARNING: destroys database data)
prod-clean:
	docker compose $(COMPOSE_FILES) down --remove-orphans --volumes

# --- Internal targets ---

check-secrets:
ifeq ($(APP_SECRET),)
	$(error APP_SECRET is not set. Create a .env.local file or export the variable)
endif
ifeq ($(CADDY_MERCURE_JWT_SECRET),)
	$(error CADDY_MERCURE_JWT_SECRET is not set. Create a .env.local file or export the variable)
endif
ifeq ($(POSTGRES_PASSWORD),)
	$(error POSTGRES_PASSWORD is not set. Create a .env.local file or export the variable)
endif
