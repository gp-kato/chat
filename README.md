## グループチャットアプリ（Laravel製）

### 概要
Laravelで構築したリアルタイム風チャットアプリ。認証機能、メッセージ送信などを実装。

### 主な機能
- ユーザー登録・ログイン（Laravel Breeze使用）
- メッセージ送受信
- チャットルームの作成・参加
- グループ管理者機能（作成者が管理者となり、グループ名や説明の編集が可能）
- メンバー招待機能（管理者からの招待メール送信、専用リンクからの参加）
- メンバー管理機能（管理者による特定メンバーのグループからの削除、退出）

### 使用技術
- Laravel 11.31
- MySQL
- Docker
- Tailwind CSS

### PHPUnitを使ったフィーチャーテスト
- 主な機能に加え、以下に関するテストを実装
　・認証
　・専用リンクからの参加
- 実施方法
  ・docker compose exec php php  src/laravel-chat/artisan test

### Reverb / Echo を用いたリアルタイム通信
- Laravel Reverb + Echo を使用してメッセージのリアルタイム配信。
- リアルタイム通信機能を利用する場合は、キューワーカーと Reverb サーバーを起動。

※別のコマンドは別のターミナルを開いてください
- 起動コマンド

- キューワーカー
　・docker compose exec php php  src/laravel-chat/artisan queue:work
- Reverb サーバー
　・docker compose exec php php  src/laravel-chat/artisan reverb:start
※リアルタイム更新を使用しない場合（ページリロードで確認する場合）は起動不要

### セットアップ方法
# リポジトリを取得
git clone [...](http://github.com/gp-kato/chat/)

# Laravelプロジェクトのディレクトリへ移動
cd src/laravel-chat

# 環境設定ファイルを作成
cp src/laravel-chat/.env.example src/laravel-chat/.env

# Dockerコンテナを起動
docker compose up -d

# PHPコンテナ内でComposer依存パッケージをインストール
docker compose exec php composer install
# Laravelのアプリケーションキーを生成
docker compose exec php php src/laravel-chat/artisan key:generate
# データベースのマイグレーション実行（テーブル作成）
docker compose exec php php src/laravel-chat/artisan migrate
# 初期データを投入
docker compose exec php php src/laravel-chat/artisan db:seed

# Nodeコンテナ内でフロントエンド依存パッケージをインストール
docker compose exec node npm install
# フロントエンドビルド（Vite）
docker compose exec node npm run dev
