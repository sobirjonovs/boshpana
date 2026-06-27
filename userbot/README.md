# Boshpana.ai — userbot (AI negotiator transport)

Telethon-based transport that lets the backend negotiate with **real apartment
owners** on Telegram. The backend is the brain (`OwnerNegotiator` decides every
message); this service is only the messenger.

It also ships a **simulation** mode that role-plays owners locally so the whole
negotiation flow is demoable with **zero Telegram credentials**.

## How it works

Backend userbot-facing endpoints (auth: `Authorization: Bearer USERBOT_API_TOKEN`):

- `GET  negotiation/tasks` — pending real-mode conversations needing first outreach.
  Each task: `{conversation_id, listing:{id,title,contact}, opening_message, account_id?}`.
- `POST negotiation/{id}/reply` body `{owner_message}` →
  `{reply: string|null, done: bool, outcome?: agreed|declined|no_response}`.
- `POST negotiation/{id}/outcome` body `{outcome, summary?}` → `{ok: true}`.

### REAL mode (`userbot.py`)

1. Poll `negotiation/tasks`.
2. Send each `opening_message` to the owner (`listing.contact.telegram`).
3. On every incoming reply from that owner → `POST .../reply`, then send the
   returned AI `reply` back.
4. When `done` is true → `POST .../outcome` and stop tracking that owner.

Owners are tracked per peer id so concurrent conversations don't cross wires.

### SIMULATION mode (`simulation.py`)

Pulls the same tasks but fabricates plausible owner replies offline and drives
the identical `reply` / `outcome` endpoints. No Telegram account, no network to
Telegram — only the backend HTTP API.

## Files

| File | Purpose |
|------|---------|
| `config.py` | Env-backed config + Telegram credential guard |
| `api.py` | httpx async client for the userbot endpoints |
| `userbot.py` | REAL Telethon transport |
| `simulation.py` | Local owner role-play loop |
| `main.py` | Entry point (`--simulation` flag) |

## Setup

```bash
cp .env.example .env
pip install -r requirements.txt
```

For REAL mode set `TG_API_ID`, `TG_API_HASH`, `TG_STRING_SESSION`
(from https://my.telegram.org; the StringSession is generated once with
Telethon's `StringSession`). These match a `telegram_accounts` profile in the
backend.

## Run

```bash
# Credential-free demo:
python main.py --simulation

# Real owner chats (requires TG_* creds):
python main.py
```

If Telegram credentials are missing in REAL mode, the userbot exits with a clear
message telling you exactly which env vars are missing and to use `--simulation`.

## Docker

```bash
docker build -t boshpana-userbot .
docker run --rm --env-file .env boshpana-userbot              # simulation (default CMD)
docker run --rm --env-file .env boshpana-userbot python main.py   # real mode
```

## Environment

| Var | Default | Notes |
|-----|---------|-------|
| `BACKEND_URL` | `http://localhost:8000/api/v1` | Backend API base |
| `USERBOT_API_TOKEN` | `local-userbot-token` | Bearer service token |
| `TG_API_ID` | — | Real mode only |
| `TG_API_HASH` | — | Real mode only |
| `TG_STRING_SESSION` | — | Real mode only |
| `POLL_INTERVAL` | `5` | Seconds between task polls |
