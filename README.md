# グループチャットアプリ

Laravelで開発した、**グループ単位でコミュニケーションできるチャットアプリ**です。

ユーザー認証、チャットルームの作成・参加、メッセージ送受信、メンバー招待・管理など、グループチャットに必要な基本機能を実装しています。

また、**Laravel Reverb + Laravel Echo**を利用したリアルタイムメッセージ配信にも対応しています。

---

## 📌 作品概要

| 項目       | 内容                            |
| -------- | ----------------------------- |
| アプリケーション | グループチャットアプリ                   |
| バックエンド   | Laravel 11.31                 |
| データベース   | MySQL                         |
| フロントエンド  | Blade / Tailwind CSS          |
| リアルタイム通信 | Laravel Reverb / Laravel Echo |
| 開発環境     | Docker                        |
| テスト      | PHPUnit                       |

### 主なポイント

* Laravel Breezeによるユーザー認証
* グループチャットルームの作成・参加
* グループ管理者によるグループ管理
* メンバー招待機能
* 招待リンクからのグループ参加
* メンバーの削除・退出
* メッセージの送受信
* Reverb / Echoによるリアルタイムメッセージ配信
* PHPUnitによるフィーチャーテスト

---

## ✨ 主な機能

### 🔐 ユーザー認証

Laravel Breezeを使用して、以下の認証機能を実装しています。

* ユーザー登録
* ログイン
* ログアウト

---

### 💬 グループチャット

ユーザー同士でグループチャットを行うことができます。

* チャットルームの作成
* チャットルームへの参加
* メッセージの送受信
* チャットルームごとのメンバー管理

---

### 👑 グループ管理者機能

グループ作成者がグループ管理者となります。

管理者は以下の操作を行うことができます。

* グループ名の編集
* グループ説明の編集
* メンバーの削除
* メンバーの管理
* メンバーの招待

---

### 📩 メンバー招待

管理者からメンバーを招待できます。

1. 管理者が招待するユーザーを指定
2. 招待メールを送信
3. メールに記載された専用リンクへアクセス
4. 招待されたユーザーがグループへ参加

開発環境では **MailHog** を使用して、送信されたメールを確認できます。

---

### ⚡ リアルタイムメッセージ配信

**Laravel Reverb + Laravel Echo**を使用しています。

メッセージ送信時にイベントを発生させ、WebSocketを利用して接続中のユーザーへメッセージをリアルタイムに配信します。

リアルタイム通信を利用しない場合は、ページをリロードしてメッセージを確認することもできます。

---

## 🛠 使用技術

* PHP
* Laravel 11.31
* Laravel Breeze
* Laravel Reverb
* Laravel Echo
* MySQL
* Docker / Docker Compose
* Tailwind CSS
* Vite
* PHPUnit

---

# 🚀 セットアップ

## 必要な環境

以下を事前にインストールしてください。

* Docker
* Git

---

## 1. リポジトリを取得

```bash
git clone http://github.com/gp-kato/chat/
cd chat
```

## 2. 環境設定ファイルを作成

```bash
cp src/laravel-chat/.env.example src/laravel-chat/.env
```

## 3. Dockerコンテナを起動

```bash
docker compose up -d
```

## 4. Composerパッケージをインストール

```bash
docker compose exec php sh -c "cd laravel-chat && composer install"
```

## 5. Laravelのアプリケーションキーを生成

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan key:generate"
```

## 6. データベースを作成

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan migrate"
```

## 7. 初期データを投入

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan db:seed"
```

シーダーでは、招待フロー確認用の固定アカウントを作成します。

- 管理者: `admin@example.com` / `password`
- 招待先ユーザー: `invitee@example.com` / `password`

招待リンクの確認時は、管理者用アカウントとは別のブラウザやシークレットモードで `invitee@example.com` にログインして参加してください。

## 8. Node.jsの依存パッケージをインストール

```bash
docker compose exec node sh -c "cd laravel-chat && npm install"
```

## 9. Viteを起動

```bash
docker compose exec node sh -c "cd laravel-chat && npm run dev"
```

---

# 🌐 アクセス

セットアップ完了後、以下から各サービスを確認できます。

| サービス          | URL                   | 用途       |
| ------------- | --------------------- | -------- |
| 💬 チャットアプリ    | http://localhost/     | アプリ本体    |
| 🗄 phpMyAdmin | http://localhost:8080 | MySQLの確認 |
| 📧 MailHog    | http://localhost:8025 | 招待メールの確認 |

まずは **http://localhost/** にアクセスしてください。

---

# ⚡ リアルタイム通信を利用する場合

リアルタイムメッセージ配信を確認する場合は、以下の2つを別々のターミナルで起動してください。

### ターミナル①：キューワーカー

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan queue:work"
```

### ターミナル②：Reverb

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan reverb:start"
```

その状態で複数のユーザーからチャットを利用すると、メッセージがリアルタイムで更新されます。

> ※ページをリロードしてメッセージを確認するだけの場合は、キューワーカーとReverbの起動は不要です。

---

# 🧪 PHPUnitによるフィーチャーテスト

主な機能についてフィーチャーテストを実装しています。

### テスト対象

* 認証
* グループへの参加申請
* 専用リンクからのグループ参加
* メンバー管理
* 招待機能
* チャットへのアクセス制御
* メッセージ取得・送信に関する権限

### テスト実行

```bash
docker compose exec php sh -c "cd laravel-chat && php artisan test"
```

---

# 🔍 確認してほしいポイント

作品を見る際は、以下の機能を順番に確認できます。

### ① ユーザー登録・ログイン

初期データでは `admin@example.com` / `password` の管理者アカウントと、`invitee@example.com` / `password` の招待先アカウントが用意されています。

まず管理者側で `admin@example.com` にログインし、グループを作成します。

↓

### ② グループを作成

グループを作成すると、そのユーザーが管理者になります。

↓

### ③ メンバーを招待

管理者からユーザーを招待します。招待対象は、事前に用意されている `invitee@example.com` を使ってください。

送信されたメールは以下から確認できます。

**MailHog**

http://localhost:8025

↓

### ④ 招待リンクから参加

メールに記載された専用リンクを開き、招待ユーザー用アカウント `invitee@example.com` でログインしてから参加します。

管理者ログインのまま招待リンクを開くと、招待先メールアドレスとログイン中ユーザーのメールアドレスが一致しないため参加できません。

↓

### ⑤ グループチャット

参加したメンバー同士でメッセージを送受信できます。

↓

### ⑥ リアルタイム通信

キューワーカーとReverbを起動し、複数ユーザーでチャットを開くことでリアルタイム更新を確認できます。

↓

### ⑦ メンバー管理

管理者からメンバーの削除などを確認できます。

---

# 📂 プロジェクト構成

```text
chat/
├── docker/
├── src/
│   └── laravel-chat/
│       ├── app/
│       ├── database/
│       ├── resources/
│       ├── routes/
│       ├── tests/
│       └── ...
├── docker-compose.yml
└── README.md
```

---

# 🎯 開発で取り組んだこと

このアプリでは、単純なメッセージ送受信だけではなく、**「誰がどのグループに対して何を操作できるか」**という権限・状態管理を意識して実装しています。

特に以下の点を実装しています。

* 認証済みユーザーのみ利用できる機能の制御
* グループ管理者と一般メンバーの権限分離
* グループ参加状態に応じたアクセス制御
* 招待リンクを利用したグループ参加フロー
* メッセージ送受信に対するアクセス制御
* PHPUnitによる主要機能の動作確認
* Reverb / Echoを利用したリアルタイム通信

---

# 📋 動作確認環境

* Docker
* Laravel 11.31
* MySQL
* Node.js / npm
* PHP
* PHPUnit

---

## License

This project is for portfolio / learning purposes.
