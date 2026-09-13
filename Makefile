# Anvision Studio — WordPress local development
# Run `make help` for the daily command list.

COMPOSE       ?= docker compose
WP            ?= $(COMPOSE) exec -T wpcli wp
THEME_DIR     := wp-content/themes/anvisionstudio
HOST_UID      ?= $(shell id -u)
HOST_GID      ?= $(shell id -g)
export HOST_UID HOST_GID

.DEFAULT_GOAL := help

.PHONY: help env setup up down restart logs ps health \
	wp shell composer npm phpcs phpcbf lint assets \
	install reset destroy

help: ## 顯示可用指令
	@awk 'BEGIN {FS = ":.*##"; printf "\nAnvision Studio WordPress\n\n"} \
		/^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@printf "\nWP-CLI 範例: make wp ARGS='plugin list'\n\n"

env: ## 若尚無 .env，從 .env.example 複製
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "Created .env from .env.example — edit passwords before make setup."; \
	else \
		echo ".env already exists."; \
	fi

setup: env ## 啟動堆疊並完成首次 WordPress 安裝
	@$(MAKE) up
	@$(MAKE) install

up: env ## 建立並啟動核心服務
	$(COMPOSE) up -d --build db wordpress wpcli mailpit adminer redis
	$(COMPOSE) ps

down: ## 停止容器（保留資料卷）
	$(COMPOSE) down

restart: ## 重啟核心服務
	$(COMPOSE) restart wordpress wpcli mailpit adminer redis db

logs: ## 跟隨服務日誌（ARGS=wordpress）
	$(COMPOSE) logs -f --tail=200 $(ARGS)

ps: ## 顯示容器狀態
	$(COMPOSE) ps

health: ## 檢查容器健康狀態與 HTTP
	$(COMPOSE) ps
	@echo "---"
	@curl -fsS -o /dev/null -w "WordPress HTTP %{http_code}\n" http://127.0.0.1:$${WP_PORT:-8080}/wp-login.php || true
	@$(COMPOSE) exec -T wpcli wp --info >/dev/null && echo "WP-CLI OK" || echo "WP-CLI not ready"

wp: ## 執行 WP-CLI（make wp ARGS='option get siteurl'）
	@if [ -z "$(ARGS)" ]; then echo "Usage: make wp ARGS='plugin list'"; exit 1; fi
	$(WP) $(ARGS)

shell: ## 進入 WordPress 容器 shell
	$(COMPOSE) exec wordpress bash

composer: ## 透過 Composer 服務執行（make composer ARGS='install'）
	$(COMPOSE) run --rm composer $(if $(ARGS),$(ARGS),install)

npm: ## 透過 Node 服務執行 npm（make npm ARGS='install'）
	$(COMPOSE) run --rm --entrypoint npm node $(if $(ARGS),$(ARGS),install)

phpcs: ## WordPress Coding Standards 檢查
	$(COMPOSE) run --rm composer phpcs

phpcbf: ## 自動修正可修復的 PHPCS 問題
	$(COMPOSE) run --rm composer phpcbf

lint: phpcs ## 執行靜態檢查

assets: ## 編譯主題前端資產
	$(COMPOSE) run --rm --workdir /app/$(THEME_DIR) --entrypoint npm node run build

install: ## 以 WP-CLI 安裝／啟用主題與核心外掛
	./scripts/install-wordpress.sh

reset: ## 重置 WordPress 資料庫並重新安裝（不刪映像）
	$(WP) db reset --yes
	./scripts/install-wordpress.sh

destroy: ## 停止並刪除容器與資料卷（不可復原）
	$(COMPOSE) down -v --remove-orphans
