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
  ・docker compose exec php php artisan test

### セットアップ方法
git clone [...](http://github.com/gp-kato/chat/)
cd src/laravel-chat
cp .env.example .env
php artisan key:generate
docker compose up -d
docker compose exec php composer install
docker compose exec php php artisan migrate
docker compose exec node npm install
npm run dev
