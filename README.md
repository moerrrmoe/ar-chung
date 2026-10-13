# ar-chung 🤖

A Laravel-based Telegram group management bot powered by AI. The bot — named **Hnin Si** — lives inside Telegram supergroups, automatically moderates spam, and replies to messages in Burmese using OpenRouter's LLM API.

---

## Features

- 🤖 **AI Replies** — Generates contextual Burmese-language replies to group messages via OpenRouter (Google Gemini Flash Lite)
- 🛡️ **Spam Detection** — Automatically deletes messages containing links, invite URLs, or @mentions
- 📬 **Telegram Webhook** — Receives and handles Telegram updates via a POST webhook endpoint
- 🧩 **Structured AI Extraction** — `/api/chat/extract` endpoint that extracts structured JSON data from natural-language prompts
- 🔐 **Sanctum API Auth** — Protected `/api/user` route via Laravel Sanctum

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.3+ |
| AI / LLM | [moe-mizrak/laravel-openrouter](https://github.com/moe-mizrak/laravel-openrouter) (OpenRouter API) |
| Telegram | [texhub/telegram](https://packagist.org/packages/texhub/telegram) |
| Auth | Laravel Sanctum |
| Database | SQLite (default) |
| Testing | PHPUnit 12 |

---

## Requirements

- PHP 8.3+
- Composer
- A [Telegram Bot Token](https://core.telegram.org/bots/tutorial)
- An [OpenRouter API Key](https://openrouter.ai/)
- A publicly reachable HTTPS URL (for the Telegram webhook)

---

## Installation

```bash
# 1. Clone the repo
git clone https://github.com/your-username/ar-chung.git
cd ar-chung

# 2. Install PHP dependencies
composer install

# 3. Copy the environment file
cp .env.example .env

# 4. Generate the application key
php artisan key:generate

# 5. Create the SQLite database and run migrations
touch database/database.sqlite
php artisan migrate

# 6. Install JS dependencies and build assets
npm install
npm run build
```

Or run everything in one command via the Composer script:

```bash
composer setup
```

---

## Configuration

Copy `.env.example` to `.env` and fill in the following values:

```env
APP_URL=https://your-public-domain.com

# Telegram
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_BOT_ID=your_telegram_bot_numeric_id

# OpenRouter
OPENROUTER_API_KEY=your_openrouter_api_key
```

> **Note:** `APP_URL` must be a publicly reachable HTTPS URL for the Telegram webhook to work. Use a tunnel tool like [tmole](https://tmole.dev) or [ngrok](https://ngrok.com) during local development.

---

## Local Development

```bash
# Start the Laravel dev server
php artisan serve

# In a separate terminal, open a public HTTPS tunnel (e.g. tmole)
tmole 8000
```

---

## Registering the Telegram Webhook

Once your server is publicly accessible, register the webhook by visiting:

```
GET https://your-public-domain.com/set-webhook
```

This calls `Telegram::bot()->setWebhook(...)` and points Telegram at your `/api/webhook` endpoint.

---

## API Endpoints

### `POST /api/webhook`
Receives incoming Telegram updates. Telegram calls this endpoint automatically after the webhook is registered.

### `POST /api/chat/extract`
Extracts structured data from a natural-language prompt using OpenRouter.

**Request:**
```json
{ "prompt": "Jane Doe is proficient in PHP and Laravel" }
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "name": "Jane Doe",
    "skills": ["PHP", "Laravel"]
  }
}
```

### `GET /api/user` _(auth:sanctum)_
Returns the authenticated user's details.

---

## Bot Behaviour

The bot only acts in **supergroup** chats and only when it has **administrator** privileges.

| Event | Action |
|---|---|
| Message contains a link, `@mention`, or `t.me`/`.com` | Message deleted + spam notice sent |
| Normal text message | AI-generated Burmese reply sent as a threaded reply |
| `/start` command | Welcome message sent |

The AI persona is **Hnin Si** — a female Burmese-language assistant. The model used is `google/gemini-3.5-flash-lite` via OpenRouter.

---

## Project Structure

```
app/
  Http/Controllers/
    ChatController.php     # /api/chat/extract handler
  Services/
    BotHandler.php         # Telegram update handler (spam filter + AI reply)
routes/
  api.php                  # /api/webhook, /api/chat/extract
  web.php                  # /set-webhook helper
database/
  migrations/
    ..._create_whitelists_table.php   # Whitelist table (group_user column)
    ..._create_personal_access_tokens_table.php
tests/
  Feature/
    ChatRouteTest.php      # Feature tests for the extract endpoint
```

---

## Running Tests

```bash
php artisan test

# Run only the chat route tests
php artisan test --filter=ChatRouteTest
```

Tests use Guzzle's `MockHandler` to intercept HTTP calls to OpenRouter — no real API calls are made.

---

## Database

| Table | Purpose |
|---|---|
| `whitelists` | Stores whitelisted `group_user` identifiers |
| `personal_access_tokens` | Sanctum API token storage |

---

## License

MIT
