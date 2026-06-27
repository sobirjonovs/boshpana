"""Telegram scraper (Telethon).

Reads channels/groups from the source's parser config (returned by
GET ingest/sources) and parses recent messages into RawListings.

Guarded: if no Telegram credentials are configured (TG_API_ID / TG_API_HASH /
TG_SESSION), `fetch()` returns [] without touching the network. Network and
auth errors are also caught so the pipeline never crashes.

Expected source config shape (from Source.config json):
    {"channels": ["@toshkent_arenda", "https://t.me/uy_joy", -1001234567890],
     "limit": 100}
"""
from __future__ import annotations

import re
from datetime import datetime
from typing import Any

from config import config
from models import RawListing

from .base import BaseScraper

_PHONE_RE = re.compile(r"(\+?998[\s\-]?\d{2}[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2})")
_TG_RE = re.compile(r"@([A-Za-z0-9_]{4,32})")


class TelegramScraper(BaseScraper):
    slug = "telegram"
    name = "Telegram"

    async def fetch(self, since: datetime) -> list[RawListing]:
        if not config.has_telegram:
            print("[telegram] no TG credentials configured — skipping")
            return []

        channels = self._channels()
        if not channels:
            print("[telegram] source config has no channels/groups — skipping")
            return []

        try:
            from telethon import TelegramClient  # noqa: WPS433 (lazy import)
            from telethon.sessions import StringSession
        except ImportError:
            print("[telegram] telethon not installed — skipping")
            return []

        limit = int(self.config.get("limit", 100))
        out: list[RawListing] = []
        try:
            client = TelegramClient(
                StringSession(config.tg_session),
                config.tg_api_id,
                config.tg_api_hash,
            )
            await client.connect()
            if not await client.is_user_authorized():
                print("[telegram] session not authorized — skipping")
                await client.disconnect()
                return []

            for channel in channels:
                out.extend(await self._read_channel(client, channel, since, limit))

            await client.disconnect()
        except Exception as exc:  # noqa: BLE001 — never crash the runner
            print(f"[telegram] error: {exc}")
            return out
        return out

    def _channels(self) -> list[Any]:
        cfg = self.config or {}
        chans = cfg.get("channels") or cfg.get("groups") or []
        if isinstance(chans, (str, int)):
            chans = [chans]
        return list(chans)

    async def _read_channel(self, client: Any, channel: Any, since: datetime, limit: int) -> list[RawListing]:
        items: list[RawListing] = []
        try:
            async for msg in client.iter_messages(channel, limit=limit):
                text = (msg.message or "").strip()
                if not text:
                    continue
                posted_at = msg.date
                if not self.within_since(posted_at, since):
                    break  # iter_messages is newest-first
                items.append(self._to_listing(channel, msg.id, text, posted_at))
        except Exception as exc:  # noqa: BLE001
            print(f"[telegram] failed reading {channel}: {exc}")
        return items

    def _to_listing(self, channel: Any, msg_id: int, text: str, posted_at: datetime) -> RawListing:
        title = text.splitlines()[0][:120] if text else "Telegram listing"
        contact: dict[str, str] = {}
        phone = _PHONE_RE.search(text)
        if phone:
            contact["phone"] = re.sub(r"[\s\-]", "", phone.group(1))
        tg = _TG_RE.search(text)
        if tg:
            contact["telegram"] = tg.group(1)

        handle = str(channel).lstrip("@").rsplit("/", 1)[-1]
        url = f"https://t.me/{handle}/{msg_id}" if not str(channel).startswith("-") else ""

        return RawListing(
            external_id=f"{handle}:{msg_id}",
            url=url,
            title=title,
            description=text,
            price=self.parse_price(text),
            contact=contact,
            rooms=self.parse_rooms(text),
            area=self.parse_area(text),
            region_hint="Toshkent",
            posted_at=posted_at,
            raw={"source": self.slug, "channel": str(channel)},
        )
