# Anvision Studio — WordPress 專業開發環境

本倉庫是 Anvision Studio 的本機 WordPress 開發堆疊：Docker Compose、MariaDB、WP-CLI、Mailpit、Adminer、Redis、Xdebug，以及 WordPress Coding Standards／Composer／主題資產工具。

WordPress 核心與資料庫存在 Docker volume，**不上 git**。版本庫只追蹤我們的 `wp-content` 主題、外掛、mu-plugin 與開發工具。官方映像可能會把 Twenty* 預設主題與 Akismet 複製進 bind mount，這些路徑已列入 `.gitignore`。

## 需求

- Docker Engine 24+ 與 Docker Compose v2
- Make（可選，指令亦可直接用 `docker compose`）
- 複製 `.env.example` 為 `.env`，並填入本機密碼（`.env` 不會進 git）

## 快速開始

```bash
cp .env.example .env
# 編輯 .env：MYSQL_*、WP_ADMIN_* 請改成你的本機密鑰，不要使用 example 佔位字串

make setup
```

`make setup` 會建置映像、啟動服務，並用 WP-CLI 安裝 WordPress、啟用 `anvisionstudio` 主題與 `anvisionstudio-core` 外掛。

| 服務 | URL |
| --- | --- |
| WordPress | http://localhost:8080 |
| 後台 | http://localhost:8080/wp-admin/ |
| Adminer | http://localhost:8081 （系統：MySQL、伺服器：`db`） |
| Mailpit | http://localhost:8025 |

停止：`make down`。刪除容器**與資料卷**（不可復原）：`make destroy`。

## 日常工作流

```bash
make up                 # 啟動
make logs ARGS=wordpress
make health             # 容器健康 + HTTP / WP-CLI
make wp ARGS='plugin list'
./bin/wp theme list
make phpcs              # WordPress Coding Standards
make phpcbf             # 自動修正
make composer ARGS='install'
make npm ARGS='install'
make assets             # 編譯主題 build/
```

首次編譯主題資產（可選；未編譯時主題仍可依 `theme.json` 運作）：

```bash
make npm ARGS='install'
make assets
# 監看：make npm ARGS='run start --workspace=@anvisionstudio/theme'
```

## WP-CLI

WP-CLI 跑在常駐的 `wpcli` 容器，與 WordPress 共用同一份核心與 `wp-content`。

```bash
make wp ARGS='core version'
make wp ARGS='option get siteurl'
make wp ARGS='user list'
make wp ARGS='post list'
make wp ARGS='cache flush'
```

## 除錯

- **PHP 錯誤**：`wp-content/debug.log`（`WP_DEBUG_LOG` 已開，畫面不顯示錯誤）。
- **Xdebug 3**：預設 `XDEBUG_MODE=debug,develop`，port `9003`，IDE key `ANVISION`。VS Code 已附 `.vscode/launch.json`。瀏覽器裝 Xdebug helper，以 trigger 模式連線（`xdebug.start_with_request=trigger`）。關閉：`.env` 設 `XDEBUG_MODE=off` 後 `make restart`。
- **信件**：WordPress 透過 mu-plugin 把 SMTP 指到 Mailpit（容器內 `mailpit:1025`）。到 http://localhost:8025 看寄出的信。
- **資料庫**：Adminer → 伺服器 `db`，帳號與密碼同 `.env` 的 `MYSQL_USER` / `MYSQL_PASSWORD`。

## 專案結構

```
docker-compose.yml          # WordPress / MariaDB / WP-CLI / Mailpit / Adminer / Redis
docker/wordpress/           # PHP 8.3 + Xdebug + Redis 擴充
docker/wpcli/               # WP-CLI 映像
wp-content/themes/anvisionstudio/
wp-content/plugins/anvisionstudio-core/
wp-content/mu-plugins/      # 本機 SMTP 與 LOCAL 後台標記
composer.json / phpcs.xml.dist
```

## 環境變數與密鑰

- 只提交 `.env.example`（佔位符）。
- 本機與 CI 以外的真實密碼、應用程式密碼、SMTP 憑證一律放 `.env` 或密鑰管理，不要寫進 compose、主題或文件。
- `scripts/install-wordpress.sh` 若偵測到 example 佔位密碼會拒絕安裝。

更細的指令、除錯與編碼規範見 [`docs/development.md`](docs/development.md) 與 [`docs/workflow.md`](docs/workflow.md)。

---

# English

Professional local WordPress stack for Anvision Studio: Docker Compose (WordPress, MariaDB, WP-CLI, Mailpit, Adminer, Redis), Xdebug-ready PHP 8.3, WPCS via Composer, and Node tooling for the starter block theme.

```bash
cp .env.example .env   # replace placeholders
make setup             # build, boot, wp core install
```

Site: http://localhost:8080 — Adminer: :8081 — Mailpit: :8025.  
Daily: `make wp ARGS='…'`, `make phpcs`, `make assets`. Secrets stay in `.env` (gitignored).
