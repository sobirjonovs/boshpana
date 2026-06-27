# 🏠 Boshpana.ai

AI-aralashgan kvartira qidiruv xizmati — Toshkent shahridagi yosh bo'ydoqlar uchun.
Foydalanuvchi Telegram botda nima izlayotganini aytadi; AI barcha manbalardagi (olx.uz,
birbir.uz, uybor.uz, joymee.uz va Telegram kanal/guruhlar) e'lonlarni analiz qiladi, uy
egalari bilan "gaplashib chiqadi" va shartlarga **rozi bo'lgan** variantlarni qaytaradi.

> AI-assisted apartment finder for young singles in Tashkent. Describe what you need in the
> Telegram bot — the AI scans listings across many sources, negotiates with the owners, and
> returns the ones whose owners agreed to your conditions.

## Arxitektura

```
boshpana/
├── backend/    Laravel 13 — REST API + Filament 4 CRM + AI "miya" (analiz + muzokara) + matching + simulation
├── bot/        Python (python-telegram-bot v21) — foydalanuvchi boti, API'ga so'rov tashlaydi
├── parser/     Python — olx/birbir/uybor/joymee scraperlari + Telegram (Telethon) → backend ingest API
├── userbot/    Python (Telethon) — REAL rejimda uy egalariga yozadigan AI "negotiator" transporti
└── CONTRACT.md — barcha komponentlar bog'lanadigan yagona spetsifikatsiya
```

**Ikki rejim:**

- **Simulation (standart)** — hammasi **offlayn** ishlaydi: e'lonlar seed ma'lumotlardan keladi,
  uy egalari bilan muzokarani AI (yoki AI o'chiq bo'lsa, evristika) o'ynaydi. Hech qanday
  Telegram akkaunti yoki jonli scraping kerak emas. Demo uchun.
- **Real** — parser jonli saytlarni o'qiydi, userbot haqiqiy Telegram profili orqali uy
  egalariga yozadi. Faqat credential (token/akkaunt) talab qiladi.

AI provayder almashtiriladi (`AI_PROVIDER`): **`deepseek`** (standart, hozircha) yoki `claude`.
Kalit bo'lmasa hammasi baribir ishlaydi — listing analizi evristik, muzokara deterministik bo'ladi.
`DEEPSEEK_API_KEY` (yoki `ANTHROPIC_API_KEY`) qo'yilsa, real AI ishlaydi. Eslatma: DeepSeek faqat
matnli (vision yo'q), shuning uchun holatni rasmlardan emas, matndan aniqlaydi.

## Tez ishga tushirish (offlayn demo)

### 1) Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env        # (allaqachon .env bor)
php artisan key:generate    # agar kerak bo'lsa
php artisan migrate:fresh --seed
php artisan serve           # http://localhost:8000
# alohida terminalda — qidiruv joblari uchun navbat ishchisi:
php artisan queue:work
```

- CRM: **http://localhost:8000/admin** — login `admin@boshpana.ai` / `password`
- API health: `GET http://localhost:8000/api/v1/health`

### 2) E'lonlarni "parse" qilish (simulation)

```bash
cd parser
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
python runner.py --simulation --source all     # backend ingest API'ga e'lon yuboradi
```

(Seed allaqachon ~36 ta demo e'lon yaratadi, shuning uchun bu ixtiyoriy.)

### 3) Telegram bot

```bash
cd bot
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env        # BOT_TOKEN= ni @BotFather tokeningiz bilan to'ldiring
python main.py
```

### 4) AI userbot (ixtiyoriy — real muzokara uchun)

```bash
cd userbot
pip install -r requirements.txt
cp .env.example .env
python main.py --simulation     # Telegram akkauntisiz muzokarani namoyish qiladi
```

## Bot oqimi (PRD)

`/start` → til tanlash → salomlashish → asosiy menyu:

```
[🔎 Qidiruv]
[💳 To'lov]   [⚙️ Sozlamalar]
```

**Qidiruv** tartibi: viloyat → tuman/shahar → narx ($, "300" yoki "300-400") → xonalar (1–5) →
holat (O'rtacha/Zo'r) → mebel (Ha/Yo'q) → komissiya (Ha/Yo'q) → metr² → rejim (o'zim/sherikchilik
→ necha kishi) → metroga yaqin → jins → oilaviy holat → **tasdiq** (tanlanganlar ko'rsatiladi) →
qidiruv boshlanadi. Qidiruv davomida spinnerli progress xabari yangilanib turadi; rozi bo'lgan
e'lon topilsa darhol manzili va manbasi bilan bildirishnoma keladi.

## Backend mantiq (PRD)

1. Har bir e'lon AI bilan analiz qilinadi (rasm + matn): viloyat/tuman, narx, xonalar, holat
   (matnda bo'lmasa rasmdan), mebel, komissiya, metroga yaqinligi, jins/oilaviy afzallik,
   sherikchilikmi — barchasi qidiruv kriteriyalariga moslab saqlanadi (`ListingAnalyzer`).
2. Parser olx/birbir/uybor/joymee + Telegram kanal/guruhlardan e'lonlarni topadi, manbasini
   saqlaydi, oxirgi 24 soatlik e'lonlarni `ingest/listings` orqali yuboradi.
3. `ListingMatcher` kriteriyalar bo'yicha ball beradi; `SearchOrchestrator` mos e'lonlar uchun
   uy egalari bilan suhbat ochadi (`OwnerNegotiator`) va rozi bo'lganlarni qaytaradi.
4. Filament CRM — e'lonlar, qidiruvlar, foydalanuvchilar, suhbatlar, to'lovlar. Rieltorlar uchun
   B2B qismi "Coming soon".

## Texnologiyalar

Laravel 13 · Filament 4 · PHP 8.4 · SQLite (standart) · AI: DeepSeek (`deepseek-chat`,
standart) yoki Claude (`claude-opus-4-8`) · python-telegram-bot 21 · Telethon · httpx.

Batafsil spetsifikatsiya: [`CONTRACT.md`](./CONTRACT.md).
