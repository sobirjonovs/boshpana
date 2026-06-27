# Boshpana.ai — Parser service

Standalone Python service that scrapes apartment listings from Tashkent sources
and POSTs normalized listings to the backend ingest API
(`POST /api/v1/ingest/listings`, CONTRACT section 3).

## Sources

| Slug        | Scraper            | Transport                                           | Status |
|-------------|--------------------|-----------------------------------------------------|--------|
| `olx`       | `OlxScraper`       | httpx — parses embedded `__PRERENDERED_STATE__` JSON | ✅ real, working |
| `birbir`    | `BirbirScraper`    | httpx — `api.birbir.uz` frontoffice REST API        | ✅ real, working |
| `uybor`     | `UyborScraper`     | httpx + selectolax (best-effort selectors)          | stub   |
| `joymee`    | `JoymeeScraper`    | httpx + selectolax (best-effort selectors)          | stub   |
| `telegram`  | `TelegramScraper`  | Telethon (channels/groups from source config)       | needs TG creds |
| `simulation`| `SimulationScraper`| offline (no network)                                | ✅ offline demo |

The **fetch → parse → normalize → RawListing → POST** pipeline is real and fault
tolerant: if a site is unreachable the scraper logs and returns `[]` instead of
crashing. Each scraper respects `PARSE_SINCE_HOURS` (last 24h by default).

### OLX.uz — `__PRERENDERED_STATE__`

OLX server-renders the full listing state into a `window.__PRERENDERED_STATE__`
script blob (`listing.listing.ads`). The scraper extracts and decodes it, giving
clean structured fields per ad: id, title, description, price (`regularPrice` →
USD), `params` (rooms / area / floor), `location` (city / district), `photos`,
and `createdTime`. Far more robust than OLX's dynamically-generated CSS classes.
Config: `{list_url, uzs_per_usd}`.

### birbir.uz — open frontoffice API

birbir's website is behind Cloudflare, but its mobile/SPA REST API is open. The
flow (reverse-engineered, in `scrapers/birbir.py`):

1. `POST /api/frontoffice/1.3.5.0/auth/anonymous` `{device:{id,name,os}}` → guest JWT
2. `PUT  /api/frontoffice/1.3.5.0/user/region` `{regionId: 1000009}` → set Toshkent
3. `POST /api/frontoffice/1.3.5.0/offer/feed` `{page, perPage, categoryUri:"kochmas-mulk/ijara/kvartiralar"}` → paginated apartment-rent offers
4. `GET  /api/frontoffice/1.3.5.0/offer/{id}` → full description (enrichment)

Prices are stored in minor units (÷100). Config (also in the backend Source row):
`{api_base, region_id, category_uri, per_page, max_pages, enrich, uzs_per_usd}`.
Point `region_id` / `category_uri` elsewhere to target other regions/categories
(see `GET /geo/region/tree` and `GET /category/tree`).

## Setup

```bash
cp .env.example .env        # fill in tokens / TG creds as needed
pip install -r requirements.txt
```

Key env vars (see `.env.example`):

- `BACKEND_URL` — backend API base (default `http://localhost:8000/api/v1`)
- `INGEST_API_TOKEN` — bearer token for the ingest endpoints
- `TG_API_ID`, `TG_API_HASH`, `TG_SESSION` — Telegram (Telethon); leave empty to
  disable the telegram scraper
- `PARSE_SINCE_HOURS` — lookback window (default 24)

## Usage

```bash
# Offline demo — generates ~15 fake Tashkent listings and ingests them.
# No network and no Telegram credentials required.
python runner.py --simulation --source all

# Run a single real marketplace scraper.
python runner.py --source olx

# Run every active source from the backend, last 48 hours.
python runner.py --source all --since-hours 48
```

The runner loads active sources from `GET /ingest/sources`, picks the matching
scraper per source (by slug, then by `type`), normalizes results, POSTs them per
source, and prints `created/updated/skipped` counts.

## Docker

```bash
docker build -t boshpana-parser .
docker run --rm --env-file .env boshpana-parser            # default: --simulation --source all
docker run --rm --env-file .env boshpana-parser python runner.py --source olx
```

## Telegram scraper config

Channels/groups come from each Telegram source's `config` JSON returned by
`GET /ingest/sources`:

```json
{ "channels": ["@toshkent_arenda", "https://t.me/uy_joy"], "limit": 100 }
```

If `TG_API_ID` / `TG_API_HASH` / `TG_SESSION` are missing, `TelegramScraper`
returns `[]` without touching the network.
