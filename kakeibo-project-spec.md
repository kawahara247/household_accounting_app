# 家計簿アプリ プロジェクト仕様書

## 概要

個人用の家計簿アプリ。収入・支出を簡単に記録・集計でき、スマホから操作可能。

---

## 技術スタック

| 項目 | 採用技術 | 選定理由 |
|------|----------|----------|
| フレームワーク | Laravel 12 | 経験を活かせる、エコシステムが充実 |
| データベース | SQLite | セットアップ不要、バックアップ簡単、個人用途に十分 |
| フロントエンド | Inertia.js + Vue 3 | SPA的な体験、Laravelとの親和性 |
| CSSフレームワーク | Tailwind CSS | レスポンシブ対応が容易 |
| 認証 | Laravel Breeze | シンプルで軽量 |
| ホスティング | Fly.io | 無料枠あり、デプロイ簡単、SQLiteとの相性良好 |

---

## 機能要件

### Phase 1（MVP）

- [ ] 収入・支出の手動入力
- [ ] カテゴリ分類
- [ ] 月次・年次集計
- [ ] レスポンシブ対応（スマホ操作）
- [ ] PWA対応（ホーム画面に追加可能）

### Phase 2

- [ ] 楽天カードCSVインポート機能
- [ ] カテゴリ自動推測

### Phase 3（将来検討）

- [ ] レシートOCR（Google Cloud Vision API等）
- [ ] グラフ・可視化機能の強化

---

## 楽天カード明細の取り込み方法

**CSV手動ダウンロード + 自動インポート方式**を採用

### 運用フロー

1. 楽天e-NAVIにログイン
2. CSV明細をダウンロード
3. アプリにアップロード（ドラッグ&ドロップ）
4. カテゴリを確認して保存

### 選定理由

- 楽天カードは公式APIを提供していない
- スクレイピングは利用規約違反のリスクあり
- 月1回、数分の作業で済む
- 他の銀行・カードにも同じ仕組みで対応可能

---

## ディレクトリ構成（案）

```
app/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       ├── TransactionController.php
│       ├── CategoryController.php
│       └── ImportController.php
├── Models/
│   ├── Transaction.php
│   └── Category.php
├── Services/
│   ├── TransactionService.php
│   └── Import/
│       ├── CsvImportService.php
│       └── RakutenCardImportService.php
resources/
├── js/
│   ├── Pages/
│   │   ├── Dashboard.vue
│   │   ├── Transactions/
│   │   │   ├── Index.vue
│   │   │   └── Create.vue
│   │   └── Import/
│   │       └── Index.vue
│   └── Components/
│       └── ...
```

---

## データベース設計（案）

### transactions テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | 主キー |
| type | string | income / expense |
| date | date | 取引日 |
| amount | integer | 金額 |
| description | string | 摘要・店舗名 |
| category_id | integer | カテゴリID |
| memo | text | メモ（任意） |
| import_source | string | 入力元（manual / rakuten_csv 等） |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### categories テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | 主キー |
| name | string | カテゴリ名 |
| type | string | income / expense |
| icon | string | アイコン（任意） |
| color | string | 表示色（任意） |

---

## ホスティング（Fly.io）

### 無料枠

- 3つの共有CPU VM
- 160GB 送信帯域/月
- Volumes でSQLiteデータ永続化

### デプロイコマンド

```bash
fly launch
fly volumes create sqlite_data --size 1
fly deploy
```

---

## 今後の検討事項

- [ ] バックアップ戦略（SQLiteファイルの定期バックアップ）
- [ ] ドメイン取得（任意）
- [ ] カテゴリの初期データ
- [ ] 楽天カードCSVのフォーマット確認

---

## 参考リンク

- [Laravel 公式ドキュメント](https://laravel.com/docs)
- [Inertia.js 公式](https://inertiajs.com/)
- [Fly.io Laravel デプロイガイド](https://fly.io/docs/laravel/)
- [Tailwind CSS](https://tailwindcss.com/)
