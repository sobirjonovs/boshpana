"""Environment configuration for the Boshpana.ai Telegram bot."""

from __future__ import annotations

import os

from dotenv import load_dotenv

load_dotenv()


def _clean(value: str | None) -> str:
    return (value or "").strip()


BOT_TOKEN: str = _clean(os.getenv("BOT_TOKEN"))
BACKEND_URL: str = _clean(os.getenv("BACKEND_URL")) or "http://localhost:8000/api/v1"
BOT_API_TOKEN: str = _clean(os.getenv("BOT_API_TOKEN")) or "local-bot-token"
DEFAULT_LANG: str = (_clean(os.getenv("DEFAULT_LANG")) or "uz").lower()

# Languages the bot can speak. Uzbek is primary.
SUPPORTED_LANGS: tuple[str, ...] = ("uz", "ru", "en")

# Progress poller cadence (seconds) and HTTP timeout (seconds).
POLL_INTERVAL: float = float(_clean(os.getenv("POLL_INTERVAL")) or "3")
HTTP_TIMEOUT: float = float(_clean(os.getenv("HTTP_TIMEOUT")) or "30")

if DEFAULT_LANG not in SUPPORTED_LANGS:
    DEFAULT_LANG = "uz"


def validate() -> None:
    """Raise a friendly error if the bot is not configured to run."""
    if not BOT_TOKEN:
        raise RuntimeError(
            "BOT_TOKEN is not set. Copy .env.example to .env and fill in BOT_TOKEN."
        )
