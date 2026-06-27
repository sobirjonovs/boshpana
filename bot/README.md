# Boshpana.ai — Telegram bot 🏠

User-facing Telegram bot for **Boshpana.ai**, the AI apartment-finder for Tashkent.
Built with [python-telegram-bot v21](https://docs.python-telegram-bot.org/) (async).
It walks a user through the ordered search flow, talks to the Laravel backend over
REST, and streams live search progress with an animated poller.

## Features

- 🌐 Multilingual UI (uz primary, ru, en) — first run asks for language.
- 🏠 Main menu: `🔎 Qidiruv` / `💳 To'lov` / `⚙️ Sozlamalar`.
- ⚙️ Settings: change language, set profile gender / marital status.
- 💳 Payment: list plans, pick a provider (Payme / Click / Uzum / Balance), get a pay link.
- 🔎 Search: the exact 13-step flow from `CONTRACT.md` §4
  (region → district → price → rooms → condition → furniture → commission →
  area → mode/partners → metro → gender → marital → confirmation).
- ⏳ Progress poller: edits ONE message every ~3s with a spinner, progress %,
  scanned/contacted/agreed counts and an `updated HH:MM:SS` line; pushes a
  separate `✅ 1 ta topildi!` notice per agreed match; shows final result cards
  when the search completes.

## Project layout

| File | Purpose |
|------|---------|
| `config.py` | Loads env (`BOT_TOKEN`, `BACKEND_URL`, `BOT_API_TOKEN`, `DEFAULT_LANG`). |
| `api.py` | Async `httpx` client for every bot-facing backend endpoint (Bearer token). |
| `i18n.py` | uz/ru/en translation dicts + `t(lang, key, **kw)` helper. |
| `keyboards.py` | Inline keyboard builders (emoji on every button) + callback prefixes. |
| `bot.py` | All handlers + the ordered search flow + progress poller. |
| `main.py` | Builds the `Application`, registers handlers, runs polling. |

## Configuration

```bash
cp .env.example .env
# then edit .env:
#   BOT_TOKEN=...            (from @BotFather)
#   BACKEND_URL=http://localhost:8000/api/v1
#   BOT_API_TOKEN=local-bot-token   (must match backend boshpana.tokens.bot)
#   DEFAULT_LANG=uz
```

## Run locally

```bash
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
python main.py
```

The bot uses long polling, so no public URL/webhook is needed. Make sure the
Laravel backend is running and reachable at `BACKEND_URL`.

## Run with Docker

```bash
docker build -t boshpana-bot .
docker run --rm --env-file .env boshpana-bot
```

> On macOS/Windows, set `BACKEND_URL=http://host.docker.internal:8000/api/v1`
> so the container can reach the backend on your host.

## Notes

- Inline taps are routed by `callback_data` prefixes (see `keyboards.CB_*`).
- Flow state lives in `context.user_data` (selected criteria, current step).
- The draft `search-request` is created once at the confirmation step, then
  `POST /search-requests/{id}/start` kicks off the backend `RunSearchJob`.
- Simulation vs. real mode is decided by the backend (`SIMULATION_DEFAULT`).
