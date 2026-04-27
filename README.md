# Household Accounting App

個人用家計簿アプリ。Laravel 12、Vue 3、Inertia.js、SQLite、Tailwind CSS を使ったローカル開発前提の構成。

## Stack

- Backend: Laravel 12, PHP 8.5, SQLite
- Frontend: Vue 3, Inertia.js, Tailwind CSS, Vite
- Auth: Laravel Breeze
- Runtime: Docker, Apache

## First Setup

1. コンテナ起動

	 `docker compose up -d --build`

2. アプリ初期化

	 `docker compose exec -T app composer run setup`

	 このコマンドで以下を実行する。

	 - Composer dependencies install
	 - `.env` 作成
	 - `APP_KEY` 生成
	 - マイグレーション
	 - Node dependencies install
	 - フロントエンド build

3. ブラウザ確認

	 `http://localhost:8080`

## Daily Development

- 開発サーバー起動

	`docker compose exec -T app composer run dev`

- 単体テスト実行

	`docker compose exec -T app php artisan test`

- フロントエンド build

	`docker compose exec -T app npm run build`

- フォーマット、静的解析、テスト

	`make check`

## Docker Notes

- `vendor` と `node_modules` は named volume で分離しているため、ホスト環境を汚さない。
- 初回起動で `vendor` が空の場合、コンテナ entrypoint が `composer install` を実行して自動復旧する。
- 完全な初期設定は `composer run setup` を実行すること。

## Troubleshooting

### `vendor/autoload.php` missing

以下を順に実行する。

1. `docker compose up -d --build`
2. `docker compose exec -T app composer run setup`

コンテナは起動時に `vendor` を自動復旧するが、`.env` 作成やフロントエンド依存解決までは行わない。

### 権限エラー

`docker compose exec -T app composer run fix-permissions`
