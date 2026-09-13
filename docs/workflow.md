# 日常工作流

## 新成員第一天

1. Clone 倉庫，確認 Docker 可用：`docker compose version`。
2. `cp .env.example .env`，改掉所有 `replace-with-…` 佔位符。
3. `make setup`，等到印出 Site / Admin / Adminer / Mailpit URL。
4. 瀏覽器開 http://localhost:8080 與 `/wp-admin/`。
5. （建議）`make npm ARGS='install'` 與 `make assets`。

## 每天開工

```bash
make up
make health
```

改 PHP（主題／外掛）後重新整理即可。改 `docker/wordpress/` 或 `php.ini` 後需要 `make up`（會 `--build`）。

## 用 WP-CLI 做事

```bash
./bin/wp post create --post_title='Hello' --post_status=publish
./bin/wp user create editor editor@example.test --role=editor
./bin/wp plugin list
./bin/wp plugin activate image-compressor
./bin/wp theme list
./bin/wp db export -   # 輸出在 stdout；勿把 dump 提交到 git
```

重寫規則若 404：`./bin/wp rewrite flush`。

## 圖片壓縮外掛

`image-compressor` 是瀏覽器端 WebP 工具（圖片不上傳）。

```bash
make wp ARGS='plugin activate image-compressor'
```

後台：媒體 → 圖片壓縮（需 `upload_files`）。前台短碼：`[image_compressor]`。

## 信件

後台「設定 → 一般」或任何 `wp_mail()` 都會進 Mailpit。用「忘記密碼」即可驗證。

## 除錯一次請求

1. VS Code 啟動 **Listen for Xdebug (WordPress)**。
2. 瀏覽器 Xdebug helper 設 IDE key `ANVISION`。
3. 在 `wp-content` 下中斷點，觸發該頁。

不想連 IDE 時把 helper 關掉即可（trigger 模式）。

## 編碼規範

送 PR 前：

```bash
make phpcs
```

只檢查自己改的檔也可以：

```bash
make composer ARGS='phpcs wp-content/themes/anvisionstudio/functions.php'
```

## 重裝與銷毀

- `make reset`：`wp db reset` 後重新跑安裝腳本（volume 中的核心保留）。
- `make destroy`：刪容器與 **全部 volume**，下次 `make setup` 會是全新站台。
- 不要把 `.env`、`*.sql`、`wp-content/uploads` 推上 git。

## 建議的變更範圍

| 要改什麼 | 改哪裡 |
| --- | --- |
| 版面、區塊、設計權杖 | `wp-content/themes/anvisionstudio` |
| 自訂文章類型、站台邏輯 | `wp-content/plugins/anvisionstudio-core` |
| 僅本機才需要的行為 | `wp-content/mu-plugins` |
| PHP / Apache / Xdebug | `docker/wordpress/` |
| 服務拓樸 | `docker-compose.yml` |
