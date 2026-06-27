"""Uybor.uz scraper (httpx + selectolax). Best-effort selectors; fault tolerant."""
from __future__ import annotations

from datetime import datetime
from urllib.parse import urljoin

import httpx
from selectolax.parser import HTMLParser

from config import config
from models import RawListing

from .base import BaseScraper


class UyborScraper(BaseScraper):
    slug = "uybor"
    name = "Uybor.uz"

    DEFAULT_LIST_URL = "https://uybor.uz/listings?category=arenda-kvartir&region=tashkent"

    async def fetch(self, since: datetime) -> list[RawListing]:
        list_url = self.config.get("list_url") or self.DEFAULT_LIST_URL
        base = self.base_url or "https://uybor.uz"
        html = await self._get(list_url)
        if not html:
            return []
        return self._parse_list(html, base, since)

    async def _get(self, url: str) -> str:
        try:
            async with httpx.AsyncClient(
                headers={"User-Agent": config.user_agent, "Accept-Language": "ru,uz,en"},
                timeout=config.http_timeout,
                follow_redirects=True,
            ) as client:
                resp = await client.get(url)
                resp.raise_for_status()
                return resp.text
        except (httpx.HTTPError, ValueError) as exc:
            print(f"[{self.slug}] fetch failed for {url}: {exc}")
            return ""

    def _parse_list(self, html: str, base: str, since: datetime) -> list[RawListing]:
        tree = HTMLParser(html)
        out: list[RawListing] = []
        cards = tree.css("div.listing-item") or tree.css("article") or tree.css("div[data-id]")
        for card in cards:
            link = card.css_first("a[href]")
            if not link:
                continue
            href = link.attributes.get("href") or ""
            url = urljoin(base, href)
            external_id = card.attributes.get("data-id") or href.rstrip("/").split("/")[-1]
            if not external_id:
                continue

            title_node = card.css_first(".listing-title") or card.css_first("h3") or link
            title = (title_node.text(strip=True) if title_node else "").strip()
            if not title:
                continue

            price_node = card.css_first(".listing-price") or card.css_first('[class*="price"]')
            price = self.parse_price(price_node.text(strip=True) if price_node else None)

            loc_node = card.css_first(".listing-address") or card.css_first('[class*="address"]')
            address = loc_node.text(strip=True) if loc_node else None

            img = card.css_first("img")
            images = []
            if img:
                src = img.attributes.get("src") or img.attributes.get("data-src")
                if src and src.startswith("http"):
                    images.append(src)

            out.append(
                RawListing(
                    external_id=str(external_id),
                    url=url,
                    title=title,
                    description=title,
                    price=price,
                    images=images,
                    rooms=self.parse_rooms(title),
                    area=self.parse_area(title),
                    address=address,
                    region_hint="Toshkent",
                    raw={"source": self.slug},
                )
            )
        return [l for l in out if self.within_since(l.posted_at, since)]
