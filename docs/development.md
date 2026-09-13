# 開發環境細節

## 服務與健康檢查

| Compose 服務 | 說明 | 健康檢查 |
| --- | --- | --- |
| `db` | MariaDB 11.4，`utf8mb4_unicode_ci` | `healthcheck.sh --connect --innodb_initialized` |
| `wordpress` | 自訂 `wordpress:php8.3-apache`，Xdebug、Redis 擴充、Composer | `curl` `/wp-login.php` |
| `wpcli` | 常駐 `sleep infinity`，`make wp` / `./bin/wp` | 依賴 WordPress healthy |
| `mailpit` | SMTP + UI | `/mailpit readyz` |
| `adminer` | 資料庫 UI | HTTP 8080 |
| `redis` | 物件快取後端（尚未強制 drop-in） | `redis-cli ping` |
| `composer` / `node` | `profiles: [tools]`，僅 `make composer` / `make npm` 啟動 | — |

`docker compose config`（搭配已填的 `.env` 或 `--env-file .env.example`）可在不上線時驗證檔案。

## PHP / Xdebug

映像：`docker/wordpress/Dockerfile`。`www-data` 的 UID/GID 對齊 `HOST_UID` / `HOST_GID`（Makefile 預設 `id -u` / `id -g`），避免 bind-mount 的 `wp-content` 權限錯亂。

Xdebug 3：

- `XDEBUG_MODE`、`XDEBUG_CONFIG` 由 Compose 注入
- `xdebug.start_with_request=trigger`（不要每個 request 都連 IDE）
- 路徑對應：容器 `/var/www/html/wp-content` → 倉庫 `wp-content`
- Linux 已設 `extra_hosts: host.docker.internal:host-gateway`

關閉除錯：`.env` 中 `XDEBUG_MODE=off`，然後 `make restart`。

## Composer 與 PHPCS

根目錄 `composer.json` 安裝 WPCS、PHPCompatibility-WP、parallel-lint。規則在 `phpcs.xml.dist`（掃描主題、核心外掛、mu-plugins）。

```bash
make composer ARGS='install'
make phpcs
make phpcbf
```

CI：`.github/workflows/quality.yml` 會跑 `docker compose config`、`composer phpcs`、`composer lint`。

## 主題資產（Node）

主題使用 `@wordpress/scripts`（官方 wp-scripts）。

```
wp-content/themes/anvisionstudio/src/index.js
wp-content/themes/anvisionstudio/src/style.scss
```

輸出到 gitignored 的 `build/`。`functions.php` 在 `build/index.asset.php` 存在時才 enqueue。

Node 20+（倉庫 `.nvmrc` 為 22）。可在主機跑 `npm install`，或 `make npm ARGS='install'`。

## Redis

Compose 已提供 Redis 7。若要啟用物件快取，請自行加入官方相容的 drop-in（例如 Redis Object Cache），並確認不要把 `object-cache.php` 的環境密鑰寫進 git。未安裝 drop-in 時，Redis 可保持空轉。

## 權限與 volume

| 路徑 | 儲存 |
| --- | --- |
| `/var/www/html`（WP 核心） | named volume `wordpress_html` |
| `./wp-content` | bind mount |
| MariaDB datadir | named volume `db_data` |

升級 WordPress 核心：`make wp ARGS='core update'`（核心仍在 volume 內）。

## Cursor Agent Skills

Cursor 會載入專案 **`.cursor/skills/`**（以及使用者全域 `~/.cursor/skills/`）。WordPress 與 WooCommerce skill 放在同一個目錄。檔案是來源複本（不是 symlink），請不要改 skill 本文。未裝店務營運 skill（例如 `navarroido/Woocommerce-skill`）或 `woocommerce-stale-pr-audit`。

| 路徑 | 來源 | Skill |
| --- | --- | --- |
| `.cursor/skills/wordpress-router/` | WordPress/agent-skills | 判斷專案類型並導向流程 |
| `.cursor/skills/wp-project-triage/` | WordPress/agent-skills | 偵測專案類型、工具與版本 |
| `.cursor/skills/wp-block-themes/` | WordPress/agent-skills | 區塊主題、`theme.json`、templates |
| `.cursor/skills/wp-plugin-development/` | WordPress/agent-skills | 外掛架構、hooks、安全性 |
| `.cursor/skills/wp-block-development/` | WordPress/agent-skills | Gutenberg `block.json`、rendering |
| `.cursor/skills/wp-wpcli-and-ops/` | WordPress/agent-skills | WP-CLI、自動化、`wp-cli.yml` |
| `.cursor/skills/abilities-api-implement/` | woocommerce/agent-skills | Woo 擴充裡的 Abilities API |
| `.cursor/skills/wc-hpos-compatibility/` | Lonsdale201/wp-agent-skills | HPOS CRUD 與相容宣告 |
| `.cursor/skills/wc-order-lifecycle-and-items/` | Lonsdale201/wp-agent-skills | 訂單狀態、line items、庫存 |
| `.cursor/skills/wc-cart-checkout-classic/` | Lonsdale201/wp-agent-skills | 傳統 cart/checkout hooks |

`npx skills add` 同時寫入 `.agents/skills/`（相同內容）與根目錄 `skills-lock.json`。Cursor 以 `.cursor/skills/` 為準。

更新 WordPress skill（只裝這六個，並同步 Cursor 目錄）：

```bash
npx skills add WordPress/agent-skills \
  --skill wordpress-router --skill wp-project-triage \
  --skill wp-block-themes --skill wp-plugin-development \
  --skill wp-block-development --skill wp-wpcli-and-ops \
  --agent cursor --copy -y

# 官方 skillpack → .cursor/skills/
git clone --depth 1 https://github.com/WordPress/agent-skills.git /tmp/wp-agent-skills
cd /tmp/wp-agent-skills
node shared/scripts/skillpack-build.mjs --clean --targets=cursor \
  --skills=wordpress-router,wp-project-triage,wp-block-themes,wp-plugin-development,wp-block-development,wp-wpcli-and-ops
node shared/scripts/skillpack-install.mjs --dest=/path/to/anvisionstudio --targets=cursor \
  --skills=wordpress-router,wp-project-triage,wp-block-themes,wp-plugin-development,wp-block-development,wp-wpcli-and-ops
```

更新 WooCommerce skill：

```bash
npx skills add woocommerce/agent-skills --skill abilities-api-implement --agent cursor --copy -y
npx skills add Lonsdale201/wp-agent-skills \
  --skill wc-hpos-compatibility --skill wc-order-lifecycle-and-items \
  --skill wc-cart-checkout-classic --agent cursor --copy -y

# 官方 Woo skillpack → .cursor/skills/
git clone --depth 1 https://github.com/woocommerce/agent-skills.git /tmp/woo-agent-skills
cd /tmp/woo-agent-skills
node shared/scripts/skillpack-build.mjs --clean --targets=cursor --skills=abilities-api-implement
node shared/scripts/skillpack-install.mjs --dest=/path/to/anvisionstudio --targets=cursor --skills=abilities-api-implement
```

Lonsdale 社群 skill 沒有 skillpack；把 `woocommerce/wc-*` 目錄原樣複製到 `.cursor/skills/` 即可，不要改本文。
