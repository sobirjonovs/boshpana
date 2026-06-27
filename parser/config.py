"""Configuration for the Boshpana.ai parser service.

Reads everything from the environment (.env supported via python-dotenv).
"""
from __future__ import annotations

import os
from dataclasses import dataclass

from dotenv import load_dotenv

load_dotenv()


def _int(name: str, default: int) -> int:
    raw = os.getenv(name, "").strip()
    try:
        return int(raw) if raw else default
    except ValueError:
        return default


@dataclass(frozen=True)
class Config:
    backend_url: str = os.getenv("BACKEND_URL", "http://localhost:8000/api/v1").rstrip("/")
    ingest_token: str = os.getenv("INGEST_API_TOKEN", "local-ingest-token")

    tg_api_id: int = _int("TG_API_ID", 0)
    tg_api_hash: str = os.getenv("TG_API_HASH", "").strip()
    tg_session: str = os.getenv("TG_SESSION", "").strip()

    parse_since_hours: int = _int("PARSE_SINCE_HOURS", 24)

    # Polite HTTP defaults for the marketplace scrapers.
    http_timeout: float = float(os.getenv("HTTP_TIMEOUT", "20"))
    user_agent: str = os.getenv(
        "PARSER_USER_AGENT",
        "Mozilla/5.0 (compatible; BoshpanaBot/1.0; +https://boshpana.ai)",
    )

    @property
    def has_telegram(self) -> bool:
        return bool(self.tg_api_id and self.tg_api_hash and self.tg_session)


config = Config()
