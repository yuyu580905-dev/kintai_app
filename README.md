# flea-market（新模擬案件\_勤怠管理アプリ）

## 環境構築

**Dockerビルド**

1. `git clone git@github.com:yuyu580905-dev/kintai_app.git`
2. DockerDesktopアプリを立ち上げる
3. `cd kintai_app/`
4. `docker-compose up -d --build`

**Laravel環境構築**

1. `docker-compose exec php bash`
2. `composer install`
3. .env.example をコピーして .env を作成

```bash
cp .env.example .env
```

4. .envに以下の環境変数を設定

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=test@example.com
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
```

5. アプリケーションキーの作成

```bash
php artisan key:generate
```

6. マイグレーションの実行

```bash
php artisan migrate
```

7. シーディングの実行

```bash
php artisan db:seed
```

## メール認証について

本アプリではメール認証にMailtrapを使用しています

### Mailtrap設定

1. Mailtrapに登録
2. Sandboxを作成
3. SMTP情報を取得
4. .envへ以下を設定

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=test@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

設定後、以下を実行してください

```bash
php artisan config:clear
```

## テスト

PHPUnitを使用して以下の機能テストを実装しています

#### 一般ユーザー

- 会員登録認証機能
- メール認証機能
- ログイン認証機能
- 日時取得機能
- ステータス確認機能
- 出勤機能
- 休憩機能
- 退勤機能
- 勤怠一覧情報取得機能
- 勤怠詳細情報取得機能
- 勤怠詳細情報修正機能
- マイ勤怠レポート機能

#### 管理者

- ログイン認証機能
- 勤怠一覧情報取得機能
- 勤怠詳細情報取得・修正機能
- ユーザー情報取得機能
- 勤怠情報承認機能

#### 公開API

- 公開API 読み取り系
- 公開API 書き込み系
- Sanctum 認証

## PHPUnit テスト実行

本アプリではテスト実行時に `demo_test` データベースを使用します

### 1. テスト用データベース作成

MySQLコンテナへ接続し、テスト用データベースを作成

```sql
CREATE DATABASE demo_test;
```

### 2. .env.testing を作成

PHPコンテナへ接続し、`.env` をコピーして `.env.testing` を作成

```bash
cp .env .env.testing
```

.env.testingを以下の内容へ変更

```env
APP_NAME=Laravel
APP_ENV=test
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql_test
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=demo_test
DB_USERNAME=root
DB_PASSWORD=root
```

### 3. テスト用アプリケーションキー生成

```bash
php artisan key:generate --env=testing
```

### 4. キャッシュの削除

```bash
php artisan config:clear
```

### 5. マイグレーションを実行してテスト用のテーブルを作成

```bash
php artisan migrate --env=testing
```

### 6. PHPUnit実行

```bash
php artisan test
```

## 使用技術（実行環境）

- PHP8.1
- Laravel8.75
- MySQL8.0
- Nginx
- Docker
- Docker Compose

## ER図

![ER図](src/er-diagram.png)

## URL

- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/
