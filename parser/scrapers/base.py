"""Abstract base class for all scrapers."""
from __future__ import annotations

import abc
import re
from datetime import datetime, timezone
from typing import Any, Optional

from models import RawListing


class BaseScraper(abc.ABC):
    """A pluggable scraper for one source.

    Subclasses set `slug` (matching the backend Source.slug) and implement
    `fetch(since)` which returns a list of normalized `RawListing` objects.
    """

    slug: str = "base"
    name: str = "Base"

    def __init__(self, source: Optional[dict[str, Any]] = None) -> None:
        # `source` is the dict returned by GET ingest/sources (id, slug, name,
        # type, base_url, config). May be None for offline/simulation use.
        self.source = source or {}
        self.base_url: str = self.source.get("base_url") or ""
        self.config: dict[str, Any] = self.source.get("config") or {}

    @abc.abstractmethod
    async def fetch(self, since: datetime) -> list[RawListing]:
        """Fetch + parse + normalize listings posted on/after `since`."""
        raise NotImplementedError

    # ---- shared normalization helpers ------------------------------------

    @staticmethod
    def parse_price(text: Optional[str]) -> Optional[int]:
        """Extract an integer USD-ish price from arbitrary text."""
        if not text:
            return None
        cleaned = text.replace("\xa0", " ")
        digits = re.sub(r"[^\d]", "", cleaned)
        if not digits:
            return None
        value = int(digits)
        # Heuristic: treat very large numbers as UZS and convert to USD.
        if value > 100_000:
            value = round(value / 12_700)
        return value or None

    @staticmethod
    def parse_rooms(text: Optional[str]) -> Optional[int]:
        if not text:
            return None
        m = re.search(r"(\d+)\s*(?:xona|комнат|room)", text, re.IGNORECASE)
        if m:
            return int(m.group(1))
        m = re.search(r"\b([1-9])\b", text)
        return int(m.group(1)) if m else None

    @staticmethod
    def parse_area(text: Optional[str]) -> Optional[int]:
        if not text:
            return None
        m = re.search(r"(\d{2,4})\s*(?:m²|m2|кв|sqm)", text, re.IGNORECASE)
        if m:
            return int(m.group(1))
        return None

    @staticmethod
    def now_utc() -> datetime:
        return datetime.now(timezone.utc)

    @staticmethod
    def within_since(posted_at: Optional[datetime], since: datetime) -> bool:
        """Keep listings without a date, or those posted on/after `since`."""
        if posted_at is None:
            return True
        if posted_at.tzinfo is None:
            posted_at = posted_at.replace(tzinfo=timezone.utc)
        return posted_at >= since
