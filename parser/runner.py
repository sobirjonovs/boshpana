"""Boshpana.ai parser runner (CLI).

Loads active sources from the backend, runs the matching scraper(s), normalizes
results to RawListing, and POSTs them to ingest/listings per source.

Examples:
    python runner.py --simulation --source all
    python runner.py --source olx
    python runner.py --source all --since-hours 48
"""
from __future__ import annotations

import argparse
import asyncio
from datetime import datetime, timedelta, timezone
from typing import Any

from api import IngestApi
from config import config
from models import RawListing
from scrapers import SCRAPERS, SimulationScraper, TelegramScraper
from scrapers.base import BaseScraper


def _build_scraper(source: dict[str, Any]) -> BaseScraper | None:
    """Pick a scraper for a backend source dict based on slug, then type."""
    slug = (source.get("slug") or "").lower()
    stype = (source.get("type") or "").lower()

    if slug in SCRAPERS:
        return SCRAPERS[slug](source)
    if stype in ("telegram_channel", "telegram_group"):
        return TelegramScraper(source)
    # Fuzzy slug match (e.g. "olx-uz" -> olx).
    for known, cls in SCRAPERS.items():
        if known in slug:
            return cls(source)
    return None


async def _run_source(api: IngestApi, scraper: BaseScraper, slug: str, since: datetime) -> dict[str, int]:
    print(f"-> running '{slug}' ({scraper.name}) since {since.isoformat()}")
    try:
        listings: list[RawListing] = await scraper.fetch(since)
    except Exception as exc:  # noqa: BLE001 — one source must not kill the run
        print(f"   [{slug}] scraper crashed: {exc}")
        listings = []
    print(f"   [{slug}] parsed {len(listings)} listings")
    counts = api.push_listings(slug, listings)
    print(
        f"   [{slug}] created={counts['created']} "
        f"updated={counts['updated']} skipped={counts['skipped']}"
    )
    return counts


async def run(args: argparse.Namespace) -> None:
    since_hours = args.since_hours if args.since_hours is not None else config.parse_since_hours
    since = datetime.now(timezone.utc) - timedelta(hours=since_hours)
    api = IngestApi()

    jobs: list[tuple[str, BaseScraper]] = []

    if args.simulation:
        # Fully offline demo path: no backend source list required.
        slug = args.source if args.source and args.source != "all" else "simulation"
        jobs.append((slug, SimulationScraper()))
    else:
        sources = api.sources()
        if not sources:
            print("No active sources returned by the backend. "
                  "Use --simulation for an offline demo.")
            return
        for source in sources:
            slug = (source.get("slug") or "").lower()
            if args.source and args.source != "all" and args.source != slug:
                continue
            scraper = _build_scraper(source)
            if scraper is None:
                print(f"-> no scraper for source '{slug}' (type={source.get('type')}) — skipped")
                continue
            jobs.append((slug, scraper))

    if not jobs:
        print("Nothing to run (no matching sources).")
        return

    totals = {"created": 0, "updated": 0, "skipped": 0}
    for slug, scraper in jobs:
        counts = await _run_source(api, scraper, slug, since)
        for k in totals:
            totals[k] += counts[k]

    print(
        f"\nDONE: created={totals['created']} "
        f"updated={totals['updated']} skipped={totals['skipped']}"
    )


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Boshpana.ai parser runner")
    p.add_argument("--source", default="all",
                   help="source slug (olx|birbir|uybor|joymee|telegram|...) or 'all'")
    p.add_argument("--simulation", action="store_true",
                   help="use the offline SimulationScraper (no network / no TG creds)")
    p.add_argument("--since-hours", type=int, default=None,
                   help="lookback window in hours (default PARSE_SINCE_HOURS / 24)")
    return p.parse_args()


if __name__ == "__main__":
    asyncio.run(run(parse_args()))
