# Boshpana.ai — Build Contract (single source of truth)

Boshpana.ai helps young singles in **Tashkent** find apartments. A user describes
what they need in a Telegram bot; an AI scans listings from many sources, "talks"
to the owners, and returns the ones whose owners agree to the conditions.

Light, friendly UI (NOT dark/black). Primary brand colour: **teal/emerald**. Brand emoji 🏠.
Primary content language: **Uzbek (uz)**; also ru, en.

Repository layout (monorepo):

```
boshpana/
├── backend/      Laravel 13 — REST API + Filament 4 CRM + AI brain + matcher + simulation
├── bot/          Python (python-telegram-bot v21, async) — user-facing bot
├── parser/       Python — scrapers (olx/birbir/uybor/joymee) + Telegram (Telethon) → POST to backend
├── userbot/      Python (Telethon) — AI negotiator transport for REAL owner chats
└── CONTRACT.md   (this file)
```

The backend spine ALREADY EXISTS (do not recreate): enums in `backend/app/Enums/`,
migrations in `backend/database/migrations/`, models in `backend/app/Models/`,
`AnthropicClient`, `routes/api.php`, config `boshpana.php`. Read those for exact
field names. Below is the authoritative summary.

---

## 1. Enums (backend/app/Enums) — string-backed

- `Language`: uz | ru | en
- `Gender`: male | female | any
- `MaritalStatus`: single | married | any
- `Condition`: average | excellent | any   ("O'rtacha" / "Zo'r")
- `TriState`: yes | no | any   (furniture, commission, near_metro)
- `SearchMode`: solo | partnership
- `SearchStatus`: draft | queued | searching | completed | cancelled | failed
- `SourceType`: marketplace | telegram_channel | telegram_group
- `ListingStatus`: active | rented | expired | hidden
- `MatchStatus`: candidate | contacting | agreed | rejected
- `ConversationStatus`: pending | contacted | replied | agreed | declined | no_response
- `MessageRole`: ai | owner | system
- `PaymentStatus`: pending | paid | failed | refunded
- `PaymentProvider`: payme | click | uzum | balance | manual
- `LeadType`: owner | realtor | agency ; `LeadStatus`: new | contacted | qualified | won | lost

## 2. Key tables / models (read migrations for the full column list)

- `telegram_users` (model `TelegramUser`): telegram_id, username, first_name, last_name,
  language, phone, gender, marital_status, is_premium, balance, free_searches_left, premium_until.
- `regions` (`Region`) / `districts` (`District`): name_uz/ru/en, slug; district has `has_metro`.
- `sources` (`Source`): slug, name, type, base_url, is_active, config(json).
- `listings` (`Listing`): source_id, listing_owner_id, external_id, url, title, description,
  images(json), price(int, USD), region_id, district_id, address, near_metro(bool), metro_station,
  rooms(int), area(int m²), condition, has_furniture(bool), has_commission(bool), amenities(json),
  gender_pref, marital_pref, mode, partners_needed, contact(json {phone,telegram}), posted_at,
  status, ai_analyzed(bool), ai_summary, ai_attributes(json), ai_confidence(float).
- `listing_owners` (`ListingOwner`): name, telegram_username, telegram_id, phone, is_realtor.
- `search_requests` (`SearchRequest`): telegram_user_id, region_id, district_id, price_min, price_max,
  currency(USD), rooms(json int[]), condition, has_furniture(TriState), has_commission(TriState),
  area_min, area_max, mode(SearchMode), partners_count, near_metro(TriState), gender, marital_status,
  free_text, status(SearchStatus), is_simulation, current_step,
  progress(0-100), scanned_count, matched_count, contacted_count, agreed_count,
  started_at, completed_at, last_progress_at.
- `search_matches` (`SearchMatch`, table search_matches): search_request_id, listing_id, conversation_id,
  score(float 0-100), score_breakdown(json), status(MatchStatus), reason, notified(bool), notified_at.
- `conversations` (`Conversation`): search_request_id, listing_id, listing_owner_id, telegram_account_id,
  channel, status(ConversationStatus), is_simulation, outcome, summary, contacted_at, closed_at.
- `messages` (`Message`): conversation_id, role(MessageRole), content, meta(json), sent_at.
- `telegram_accounts` (`TelegramAccount`): label, phone, username, session(encrypted), is_active,
  is_simulation, daily_limit, sent_today.
- `plans` (`Plan`) / `subscriptions` / `payments` (`Payment`) / `crm_leads` (`CrmLead`).
- `users` (`User`): Filament CRM **admins** (separate from telegram_users). Already implements FilamentUser.

## 3. API contract (base `/api/v1`, JSON). Auth = bearer service token.

Tokens (config `boshpana.tokens`): bot / ingest / userbot. Header: `Authorization: Bearer <token>`.

### Bot-facing (service.token:bot)
- `POST users/sync` body `{telegram_id, username?, first_name?, last_name?, language?}` → `{data: UserResource}` (upsert).
- `GET users/{telegramId}` → `{data: UserResource}`.
- `PATCH users/{telegramId}` body `{language?, phone?, gender?, marital_status?}` → `{data: UserResource}`.
- `GET regions?lang=uz` → `{data: [RegionResource]}` (id, slug, name, name_uz/ru/en).
- `GET regions/{region}/districts?lang=uz` → `{data: [DistrictResource]}` (id, slug, name, has_metro).
- `POST search-requests` body `{telegram_id, region_id?, district_id?, price_min?, price_max?,
  rooms?:[int], condition?, has_furniture?, has_commission?, area_min?, area_max?, mode?, partners_count?,
  near_metro?, gender?, marital_status?, free_text?, is_simulation?}` → `{data: SearchRequestResource}` (status=draft).
  All criteria optional; create a draft then PATCH, or send everything at once.
- `PATCH search-requests/{id}` body = any subset of criteria → `{data: SearchRequestResource}`.
- `POST search-requests/{id}/start` → dispatches `RunSearchJob`; returns `{data: SearchRequestResource}` (status=queued/searching).
- `POST search-requests/{id}/cancel` → status=cancelled.
- `GET search-requests/{id}` → `{data: SearchRequestResource}` incl. progress + summary fields.
- `GET search-requests/{id}/results` → `{data: [SearchMatchResource]}` (listing summary + source + score + status + owner reply).
- `GET plans` → `{data: [PlanResource]}`.
- `POST payments` body `{telegram_id, plan_id, provider}` → `{data: PaymentResource, pay_url?: string}`.
- `GET payments/{id}` → `{data: PaymentResource}`.

### Parser-facing (service.token:ingest)
- `GET ingest/sources` → `{data: [{id, slug, name, type, base_url, config}]}` (active sources + their parser config).
- `POST ingest/listings` body `{source: "<source-slug>", listings: [RawListing...]}` → `{created, updated, skipped}`.
  RawListing = `{external_id, url, title, description, price?, currency?, images?:[url],
  contact?:{phone,telegram}, rooms?, area?, address?, region_hint?, posted_at?(ISO8601), raw?:{}}`.
  Ingest upserts by (source_id, external_id) and dispatches `AnalyzeListingJob` for new/updated rows.

### Userbot-facing (service.token:userbot)
- `GET negotiation/tasks` → `{data: [{conversation_id, listing:{id,title,contact}, opening_message, account_id?}]}`
  (real-mode conversations in status=pending that need first outreach).
- `POST negotiation/{conversation}/reply` body `{owner_message}` → `{reply: string|null, done: bool, outcome?: agreed|declined|no_response}`.
  Backend's `OwnerNegotiator` logs both turns and decides the next AI message (null + done when finished).
- `POST negotiation/{conversation}/outcome` body `{outcome, summary?}` → `{ok: true}`.

### Resource shapes (App\Http\Resources)
- `UserResource`: id, telegram_id, username, first_name, language, phone, gender, marital_status,
  is_premium, balance, free_searches_left, can_search(bool).
- `RegionResource`: id, slug, name (localised by ?lang), name_uz, name_ru, name_en.
- `DistrictResource`: id, region_id, slug, name, has_metro.
- `SearchRequestResource`: id, status, is_simulation, all criteria (region {id,name}, district {id,name},
  price_min/max, rooms, condition, has_furniture, has_commission, area_min/max, mode, partners_count,
  near_metro, gender, marital_status), progress, scanned_count, matched_count, contacted_count,
  agreed_count, summary(human-readable multiline string of selected criteria), created_at.
- `SearchMatchResource`: id, score, status, reason, notified, listing {id, title, price, currency, rooms,
  area, condition, region, district, near_metro, url, source {name, type}, images, contact}, owner_reply.
- `PlanResource`, `PaymentResource` analogous.

## 4. Ordered search flow (bot steps) — keep this exact order

1. Region (viloyat) — inline buttons (from `GET regions`)
2. District (tuman/shahar) — inline buttons (from `GET regions/{id}/districts`)
3. Price (USD) — free text; integer or range. Accept "300", "300-400", "300 400". Parse → price_min/price_max.
4. Rooms — inline multi-select 1/2/3/4/5 (toggle, then "Davom etish"). → rooms:[int]
5. Condition — inline: O'rtacha / Zo'r / Farqi yo'q (average/excellent/any)
6. Furniture (mebel, konditsioner, kir mashina...) — inline: Ha / Yo'q / Farqi yo'q (TriState)
7. Commission/realtor (maklerlik) — inline: Ha / Yo'q / Farqi yo'q (TriState)
8. Area m² — free text; single or range; optional ("O'tkazib yuborish"). → area_min/area_max
9. Mode — inline: Faqat o'zim / Sherikchilik (solo/partnership)
   - if partnership → partners_count: O'zim / 2 / 3 / 4
10. Near metro — inline: Ha / Yo'q / Farqi yo'q (TriState)
11. Gender — inline: Erkak / Ayol / Farqi yo'q
12. Marital status — inline: Bo'ydoq / Uylangan / Farqi yo'q
13. Confirmation — show the full summary (use SearchRequestResource.summary) + buttons:
    [✅ Qidiruvni boshlash] [✏️ O'zgartirish]. On confirm → POST start, then poll status.

Progress UX: after start, poll `GET search-requests/{id}` every ~3s. Edit ONE message showing an
animated spinner (cycle ⏳🔄… or braille frames), progress %, scanned/contacted/agreed counts, and an
"updated at HH:MM:SS" line. When a NEW agreed match appears (agreed_count increases / results grow),
send a separate notification "✅ 1 ta topildi!" with the listing address + source link. Continue until
status=completed, then show final summary + results.

## 5. Backend classes to IMPLEMENT (exact names — routes/seeders already reference them)

Controllers `App\Http\Controllers\Api\`: `UserController`, `RegionController`, `SearchRequestController`,
`PlanController`, `PaymentController`, `IngestController`, `NegotiationController`.
Resources `App\Http\Resources\`: `UserResource`, `RegionResource`, `DistrictResource`,
`SearchRequestResource`, `SearchMatchResource`, `ListingResource`, `PlanResource`, `PaymentResource`.
Form requests as needed in `App\Http\Requests\`.

AI/logic `App\Services\`:
- `Ai\AnthropicClient` (EXISTS): `enabled()`, `text($messages,$system,$opts)`,
  `structured($messages,$schema,$system,$opts)`, static `imageBlock($url)`, `textBlock($text)`. Model claude-opus-4-8.
- `Ai\ListingAnalyzer`: `analyze(Listing $l): void` — uses AnthropicClient (text + images) with a JSON schema to
  fill region/district/price/rooms/area/condition/has_furniture/has_commission/near_metro/gender_pref/
  marital_pref/mode/partners_needed/amenities/ai_summary/ai_confidence, set ai_analyzed=true, analyzed_at=now.
  If `!AnthropicClient::enabled()`, fall back to a deterministic keyword/heuristic analyzer (regex on
  title/description for price, rooms, "qizlar/ayollar", "oilaga", "metro", "mebel", "konditsioner",
  "vositachi/makler/komissiya", region/district names). MUST work offline.
- `Matching\ListingMatcher`: `match(SearchRequest $r): Collection` of [listing_id, score(0-100), breakdown[]].
  Weighted criteria scoring (location, price within range, rooms, condition, furniture, commission, area,
  metro, gender, marital, mode/partnership). Hard filters vs soft scoring; respect min_score config.
- `Search\OwnerNegotiator`: `nextMessage(Conversation $c, ?string $ownerMessage): array` →
  ['reply'=>?string,'done'=>bool,'outcome'=>?string]. Persists messages. In SIMULATION it also generates the
  owner's replies (AI role-plays the owner from the listing; or heuristic agree/decline if AI disabled).
- `Search\SearchOrchestrator`: `run(SearchRequest $r): void` — the core. Steps:
  (1) status=searching, started_at=now. (2) candidates = ListingMatcher->match (cap max_candidates).
  (3) create SearchMatch rows (status=candidate). (4) For each candidate (respecting is_simulation):
  open a Conversation, run OwnerNegotiator to conclude agreed/declined; if agreed → match.status=Agreed,
  notify (set notified). (5) Update progress/scanned/contacted/agreed + last_progress_at incrementally so the
  bot's poller sees live movement (save frequently, small sleeps ok). (6) status=completed, completed_at=now.

Jobs `App\Jobs\`: `RunSearchJob(SearchRequest)` → calls SearchOrchestrator; `AnalyzeListingJob(Listing)` → ListingAnalyzer.

Bot notifications: store notify-worthy results as SearchMatch with notified=false; the bot discovers them by
polling results. (No push needed from backend.)

## 6. Filament 4 CRM (backend/app/Filament) — IMPORTANT version notes

Filament v4.x is installed (NOT v3). Panel provider: `app/Providers/Filament/AdminPanelProvider.php`.
Generate-friendly conventions for v4:
- Resources extend `Filament\Resources\Resource`. Forms use `Filament\Schemas\Schema` (method
  `form(Schema $schema): Schema` returning `$schema->components([...])`). Tables use
  `Filament\Tables\Table` (`table(Table $table): Table` → `$table->columns([...])->filters([...])->recordActions([...])`).
- Form fields: `Filament\Forms\Components\{TextInput,Select,Textarea,Toggle,DatePicker,KeyValue}`.
- Table columns: `Filament\Tables\Columns\{TextColumn,IconColumn,ImageColumn,BadgeColumn?}` (use TextColumn->badge()).
- Schemas/infolists components live under `Filament\Schemas\Components\{Section,Grid,...}` in v4.
- Enums implement HasLabel/HasColor → use `->badge()` on columns; selects use `->options(EnumClass::class)`.
- Pages: `Filament\Resources\Pages\{ListRecords,CreateRecord,EditRecord,ViewRecord}`.
- Each Resource: `getPages()` returns the routes. Use `static::getModel()`.
- Navigation: set `protected static \BackedEnum|string|null $navigationIcon` (heroicon-o-*) and group via
  `protected static \UnitEnum|string|null $navigationGroup`.
If unsure about a v4 signature, prefer the artisan-generated skeleton already present (if any) and keep it minimal but working.

Resources to build (read-mostly CRM): Listings, SearchRequests, TelegramUsers, Conversations (with Messages
relation manager), Sources, Payments, CrmLeads. Dashboard widgets: stats (users, listings, active searches,
agreed matches), latest searches table. A "Realtorlar uchun (Coming soon)" placeholder page. Theme: light,
primary teal/emerald. Brand name "Boshpana.ai".

## 7. Python services — shared conventions

- Python 3.11+. Each service: own folder, `requirements.txt`, `.env.example`, `README.md`, `config.py`
  (reads env), a small `api.py` HTTP client (httpx) for the backend, `Dockerfile`.
- Backend base URL env `BACKEND_URL` (default `http://localhost:8000/api/v1`). Service token via env.
- `bot/`: python-telegram-bot v21 (async, `Application`). Token env `BOT_TOKEN`. Implements the full ordered
  search flow above with inline keyboards, i18n (uz/ru/en in `locales/` dicts), settings (language switch),
  payment menu (plans + provider buttons), progress poller with spinner. Emojis on every button.
- `parser/`: pluggable `BaseScraper` per source: `OlxScraper, BirbirScraper, UyborScraper, JoymeeScraper`
  (httpx + selectolax/BeautifulSoup; tolerate failures, last-24h default) + `TelegramScraper` (Telethon, reads
  channels/groups from `ingest/sources` config) + `SimulationScraper` (generates realistic fake listings offline).
  A `runner.py`/CLI normalizes and POSTs to `ingest/listings`. Real selectors may be stubs but the pipeline +
  normalization + POST must be real and runnable (SimulationScraper works with no network).
- `userbot/`: Telethon client logging into a `telegram_accounts` profile (StringSession via env). Polls
  `negotiation/tasks`, sends opening_message to owner, on each owner reply calls `negotiation/{id}/reply`,
  sends back the AI reply, posts final `outcome`. Includes a `--simulation` mode that role-plays owners locally
  (no real Telegram) for safe demos.

## 8. Conventions

- Money is USD integers. Currency label always "$".
- Localised names: `name(?lang)` on Region/District/Plan.
- Don't touch files outside your assigned set. Shared/wiring files (routes/api.php, DatabaseSeeder,
  AdminPanelProvider, config) are owned by the orchestrator — do not edit them.
- Keep code clean, typed, and runnable. Match the style of the existing spine files.
