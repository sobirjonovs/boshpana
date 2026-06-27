"""OLX.uz scraper — parses the embedded ``window.__PRERENDERED_STATE__`` JSON.

OLX server-renders the full listing state into a ``__PRERENDERED_STATE__``
script blob (``listing.listing.ads``), which is far more reliable than the
dynamically-generated CSS classes. Each ad carries id, title, description,
price, params (rooms/area/floor), location, photos and createdTime.

A CSS-card fallback is kept for the (unlikely) case the blob is absent. Network
errors are caught and ``[]`` returned — one source never crashes the run.

Source.config overrides: {list_url, uzs_per_usd}.
"""
from __future__ import annotations

import html as _html
import json
import re
from datetime import datetime
from urllib.parse import urljoin

import httpx
from selectolax.parser import HTMLParser

from config import config
from models import RawListing

from .base import BaseScraper

BROWSER_UA = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
)


class OlxScraper(BaseScraper):
    slug = "olx"
    name = "OLX.uz"

    # Tashkent long-term apartment rent, freshest first.
    DEFAULT_LIST_URL = (
        "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/"
        "?search%5Border%5D=created_at:desc"
    )

    async def fetch(self, since: datetime) -> list[RawListing]:
        list_url = self.config.get("list_url") or self.DEFAULT_LIST_URL
        base = self.base_url or "https://www.olx.uz"
        rate = float(self.config.get("uzs_per_usd") or 12950)

        html = await self._get(list_url)
        if not html:
            return []

        state = self._extract_state(html)
        if state:
            ads = self._dig(state, ["listing", "listing", "ads"]) or []
            listings = [self._map_ad(ad, base, rate) for ad in ads if ad.get("id")]
        else:
            print(f"[{self.slug}] __PRERENDERED_STATE__ not found — using CSS fallback")
            listings = self._parse_cards(html, base)

        return [l for l in listings if l and self.within_since(l.posted_at, since)]

    async def _get(self, url: str) -> str:
        try:
            async with httpx.AsyncClient(
                headers={"User-Agent": BROWSER_UA, "Accept-Language": "ru,uz,en"},
                timeout=config.http_timeout,
                follow_redirects=True,
            ) as client:
                resp = await client.get(url)
                resp.raise_for_status()
                return resp.text
        except httpx.HTTPError as exc:
            print(f"[{self.slug}] fetch failed for {url}: {exc}")
            return ""

    # ---- __PRERENDERED_STATE__ parsing -----------------------------------

    @staticmethod
    def _extract_state(html: str) -> dict | None:
        m = re.search(r"window\.__PRERENDERED_STATE__\s*=\s*", html)
        if not m:
            return None
        i = m.end()
        # the value is a double-quoted JSON string literal (handles \u, \", UTF-8)
        while i < len(html) and html[i] != '"':
            i += 1
        if i >= len(html):
            return None
        j = i + 1
        while j < len(html):
            if html[j] == "\\":
                j += 2
                continue
            if html[j] == '"':
                break
            j += 1
        try:
            inner = json.loads(html[i:j + 1])  # -> the JSON string
            return json.loads(inner)            # -> the object
        except (json.JSONDecodeError, ValueError):
            return None

    @staticmethod
    def _dig(data: dict, path: list[str]):
        cur = data
        for key in path:
            if not isinstance(cur, dict):
                return None
            cur = cur.get(key)
        return cur

    def _map_ad(self, ad: dict, base: str, rate: float) -> RawListing | None:
        try:
            params = {p.get("key"): (p.get("normalizedValue") or p.get("value"))
                      for p in ad.get("params", []) if isinstance(p, dict)}
            location = ad.get("location") or {}
            price = self._price_usd(ad.get("price") or {}, rate)

            city = location.get("cityName") or ""
            district = location.get("districtName") or ""
            region_hint = self._region_hint(location)
            address = ", ".join(x for x in [district, city] if x) or None

            images = [u for u in (ad.get("photos") or []) if isinstance(u, str)][:6]

            return RawListing(
                external_id=str(ad.get("id")),
                url=ad.get("url") or urljoin(base, ad.get("urlPath", "")),
                title=(ad.get("title") or "").strip(),
                description=self._clean_html(ad.get("description") or ""),
                price=price,
                currency="USD",
                images=images,
                contact=self._contact(ad),
                rooms=self._int(params.get("number_of_rooms")) or self.parse_rooms(ad.get("title")),
                area=self._int(params.get("total_area") or params.get("total_living_area")),
                address=address,
                region_hint=region_hint,
                posted_at=self._parse_dt(ad.get("createdTime") or ad.get("lastRefreshTime")),
                raw={
                    "source": self.slug,
                    "city": city,
                    "district": district,
                    "floor": params.get("floor"),
                    "furnished": params.get("furnished"),
                },
            )
        except Exception as exc:  # noqa: BLE001 — skip a malformed ad, keep the rest
            print(f"[{self.slug}] skipped an ad: {exc}")
            return None

    @staticmethod
    def _price_usd(price: dict, rate: float):
        regular = price.get("regularPrice") or {}
        value = regular.get("value")
        if not value:
            return None
        currency = (regular.get("currencyCode") or "UZS").upper()
        if currency == "UZS":
            return round(value / rate) or None
        return round(value) or None

    @staticmethod
    def _region_hint(location: dict) -> str | None:
        city = (location.get("cityName") or "").lower()
        if "ташкент" in city or "toshkent" in city or "tashkent" in city:
            return "Toshkent shahri"
        return location.get("regionName") or location.get("cityName")

    @staticmethod
    def _contact(ad: dict) -> dict:
        contact = {}
        name = (ad.get("contact") or {}).get("name") or (ad.get("user") or {}).get("name")
        if name:
            contact["name"] = name
        return contact

    @staticmethod
    def _int(value):
        if value is None:
            return None
        digits = re.sub(r"[^\d]", "", str(value))
        return int(digits) if digits else None

    @staticmethod
    def _clean_html(text: str) -> str:
        text = re.sub(r"<\s*br\s*/?\s*>", "\n", text, flags=re.IGNORECASE)
        text = re.sub(r"<[^>]+>", "", text)
        return _html.unescape(text).strip()

    @staticmethod
    def _parse_dt(value):
        if not value:
            return None
        try:
            return datetime.fromisoformat(str(value).replace("Z", "+00:00"))
        except ValueError:
            return None

    # ---- CSS fallback ----------------------------------------------------

    def _parse_cards(self, html: str, base: str) -> list[RawListing]:
        tree = HTMLParser(html)
        out: list[RawListing] = []
        for card in tree.css('div[data-cy="l-card"]'):
            link = card.css_first("a")
            if not link:
                continue
            href = link.attributes.get("href") or ""
            external_id = card.attributes.get("id") or href.rstrip("/").split("/")[-1]
            title_node = card.css_first("h6") or card.css_first("h4") or link
            title = (title_node.text(strip=True) if title_node else "").strip()
            if not (external_id and title):
                continue
            price_node = card.css_first('p[data-testid="ad-price"]')
            img = card.css_first("img")
            images = []
            if img:
                src = img.attributes.get("src") or img.attributes.get("data-src")
                if src and src.startswith("http"):
                    images.append(src)
            out.append(RawListing(
                external_id=str(external_id),
                url=urljoin(base, href),
                title=title,
                description=title,
                price=self.parse_price(price_node.text(strip=True) if price_node else None),
                images=images,
                rooms=self.parse_rooms(title),
                area=self.parse_area(title),
                region_hint="Toshkent shahri",
                raw={"source": self.slug},
            ))
        return out
