# 除錯指南

## 日誌

| 來源 | 位置 |
| --- | --- |
| PHP / WP_DEBUG_LOG | `wp-content/debug.log`（已 gitignore） |
| Apache / 容器 | `make logs ARGS=wordpress` |
| 資料庫 | `make logs ARGS=db` |
| 信件 | Mailpit UI，或 `make logs ARGS=mailpit` |

`WP_DEBUG_DISPLAY` 為 false，避免本機 HTML 被 notice 打斷。

## Xdebug（VS Code）

1. 安裝建議擴充（`.vscode/extensions.json` 含 PHP Debug）。
2. `.env`：`XDEBUG_MODE=debug,develop`，`XDEBUG_CLIENT_PORT=9003`。
3. 啟動 Listen for Xdebug。
4. 以 trigger（瀏覽器擴充或 `XDEBUG_SESSION=ANVISION` cookie／query）打該請求。

PhpStorm：Servers 對應 `/var/www/html/wp-content` → 專案 `wp-content`，port 9003，IDE key `ANVISION`。

## 常見問題

**`make setup` 拒絕安裝**  
`.env` 仍是 `.env.example` 的佔位字串。改掉 `MYSQL_PASSWORD`、`MYSQL_ROOT_PASSWORD`、`WP_ADMIN_USER`、`WP_ADMIN_PASSWORD`。

**WP-CLI: Error: This does not seem to be a WordPress installation**  
核心 volume 尚未初始化。看 `docker compose logs wordpress`，等 healthcheck 綠燈再 `make install`。

**主題樣式沒編譯**  
沒有 `build/` 時只靠 `theme.json`。執行 `make npm ARGS='install'` 與 `make assets`。

**Xdebug 連不上**  
確認 `host.docker.internal`（Compose 已加 `host-gateway`）、本機 9003 未被佔用、helper 已觸發 session。可暫時改 `xdebug.start_with_request=yes` 做診斷，不要當成日常預設。

**信件沒出現**  
確認 WordPress 容器連得到 `mailpit:1025`（`docker compose exec wordpress ping -c1 mailpit` 若無 ping，改看 `make logs ARGS=mailpit`）。mu-plugin `000-anvision-local.php` 必須存在。
