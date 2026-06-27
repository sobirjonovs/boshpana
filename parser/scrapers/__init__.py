"""Scraper registry for the Boshpana.ai parser service."""
from __future__ import annotations

from .base import BaseScraper
from .birbir import BirbirScraper
from .joymee import JoymeeScraper
from .olx import OlxScraper
from .simulation import SimulationScraper
from .telegram import TelegramScraper
from .uybor import UyborScraper

# Map source slug -> scraper class (marketplace scrapers).
SCRAPERS: dict[str, type[BaseScraper]] = {
    OlxScraper.slug: OlxScraper,
    BirbirScraper.slug: BirbirScraper,
    UyborScraper.slug: UyborScraper,
    JoymeeScraper.slug: JoymeeScraper,
}

__all__ = [
    "BaseScraper",
    "OlxScraper",
    "BirbirScraper",
    "UyborScraper",
    "JoymeeScraper",
    "TelegramScraper",
    "SimulationScraper",
    "SCRAPERS",
]
