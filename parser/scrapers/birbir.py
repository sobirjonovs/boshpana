"""birbir.uz scraper — real frontoffice API.

birbir.uz's website is behind Cloudflare, but its mobile/SPA REST API
(``api.birbir.uz``) is open. The flow, reverse-engineered and verified:

  1. POST /auth/anonymous  {device:{id,name,os}}        -> guest accessToken (JWT)
  2. PUT  /user/region     {regionId: <id>}             -> set the session region
  3. POST /offer/feed      {page, perPage, categoryUri} -> paginated offers
  4. GET  /offer/{id}                                   -> full description (enrich)

Defaults target Toshkent shahri (regionId 1000009) + the apartment-rent
category ("kochmas-mulk/ijara/kvartiralar"). Both are overridable via the
backend Source.config: {region_id, category_uri, per_page, max_pages, enrich,
uzs_per_usd}.
"""
from __future__ import annotations

import uuid
from datetime import datetime, timezone
from typing import Any, Optional

import httpx

from config import config
from models import RawListing

from .base import BaseScraper

API_BASE = "https://api.birbir.uz/api/frontoffice/1.3.5.0"
BROWSER_UA = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
)


class BirbirScraper(BaseScraper):
    slug = "birbir"
    name = "birbir.uz"

    DEFAULT_REGION_ID = 1000009  # Toshkent shahri
    DEFAULT_CATEGORY_URI = "kochmas-mulk/ijara/kvartiralar"  # Ko'chmas mulk > Ijara > Kvartiralar
    PHOTO_SIZE = "640x480"

    async def fetch(self, since: datetime) -> list[RawListing]:
        api_base = self.config.get("api_base") or API_BASE
        region_id = int(self.config.get("region_id") or self.DEFAULT_REGION_ID)
        category_uri = self.config.get("category_uri") or self.DEFAULT_CATEGORY_URI
        per_page = int(self.config.get("per_page") or 30)
        max_pages = int(self.config.get("max_pages") or 4)
        enrich = bool(self.config.get("enrich", True))
        rate = float(self.config.get("uzs_per_usd") or 12950)

        headers = {
            "User-Agent": BROWSER_UA,
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Accept-Language": "uz",
        }

        out: list[RawListing] = []
        try:
            async with httpx.AsyncClient(base_url=api_base, headers=headers,
                                         timeout=config.http_timeout, follow_redirects=True) as client:
                token = await self._auth(client)
                if not token:
                    print(f"[{self.slug}] could not obtain guest token — skipping")
                    return []
                client.headers["Authorization"] = f"Bearer {token}"
                await self._set_region(client, region_id)

                for page in range(1, max_pages + 1):
                    items = await self._feed(client, page, per_page, category_uri)
                    if not items:
                        break
                    stop = False
                    for offer in items:
                        listing = self._map(offer, rate)
                        if not self.within_since(listing.posted_at, since):
                            stop = True  # feed is newest-first
                            continue
                        if enrich:
                            await self._enrich(client, offer.get("id"), listing)
                        out.append(listing)
                    if stop:
                        break
        except httpx.HTTPError as exc:
            print(f"[{self.slug}] API error: {exc}")

        return out

    # ---- API steps -------------------------------------------------------

    async def _auth(self, client: httpx.AsyncClient) -> Optional[str]:
        device = {"id": str(uuid.uuid4()), "name": "boshpana", "os": "web"}
        try:
            r = await client.post("/auth/anonymous", json={"device": device})
            r.raise_for_status()
            return r.json().get("content", {}).get("accessToken")
        except httpx.HTTPError as exc:
            print(f"[{self.slug}] auth failed: {exc}")
            return None

    async def _set_region(self, client: httpx.AsyncClient, region_id: int) -> None:
        try:
            await client.put("/user/region", json={"regionId": region_id})
        except httpx.HTTPError as exc:
            print(f"[{self.slug}] set region failed (continuing): {exc}")

    async def _feed(self, client: httpx.AsyncClient, page: int, per_page: int,
                    category_uri: str) -> list[dict[str, Any]]:
        body = {"page": page, "perPage": per_page, "categoryUri": category_uri}
        try:
            r = await client.post("/offer/feed", json=body)
            r.raise_for_status()
            return r.json().get("content", {}).get("items", []) or []
        except httpx.HTTPError as exc:
            print(f"[{self.slug}] feed page {page} failed: {exc}")
            return []

    async def _enrich(self, client: httpx.AsyncClient, offer_id: Any, listing: RawListing) -> None:
        if not offer_id:
            return
        try:
            r = await client.get(f"/offer/{offer_id}")
            if r.status_code != 200:
                return
            content = r.json().get("content", {})
            desc = (content.get("description") or "").strip()
            if desc:
                listing.description = desc
            feats = content.get("features")
            if isinstance(feats, list):
                listing.raw["features"] = feats
                self._apply_features(feats, listing)
        except httpx.HTTPError:
            pass

    # ---- mapping ---------------------------------------------------------

    def _map(self, offer: dict[str, Any], rate: float) -> RawListing:
        oid = str(offer.get("id"))
        web_uri = offer.get("webUri") or ""
        url = "https://birbir.uz/" + web_uri.lstrip("/")
        title = (offer.get("title") or "").strip()

        price = self._price_usd(offer.get("price") or {}, rate)
        region = offer.get("region") or {}
        region_hint = region.get("title")  # e.g. "Toshkent"

        posted_at = None
        ts = offer.get("publishedAt")
        if isinstance(ts, (int, float)):
            posted_at = datetime.fromtimestamp(ts / 1000, tz=timezone.utc)

        return RawListing(
            external_id=oid,
            url=url,
            title=title,
            description="",  # filled by _enrich
            price=price,
            currency="USD",
            images=self._images(offer),
            contact=self._contact(offer),
            rooms=self.parse_rooms(title),
            area=self.parse_area(title),
            address=region.get("whereTitle") or region_hint,
            region_hint=region_hint,
            posted_at=posted_at,
            raw={"source": self.slug, "region_key": region.get("key"), "slug": offer.get("slug")},
        )

    @staticmethod
    def _price_usd(price: dict[str, Any], rate: float) -> Optional[int]:
        value = price.get("value")
        if not value:
            return None
        major = value / 100.0  # birbir stores minor units (cents / tiyin)
        currency = (price.get("currency") or "USD").upper()
        if currency == "UZS":
            return round(major / rate) or None
        return round(major) or None

    def _images(self, offer: dict[str, Any]) -> list[str]:
        urls: list[str] = []
        self._add_photo(offer.get("primaryPhoto") or {}, urls)
        for ph in (offer.get("photos") or [])[:5]:
            self._add_photo(ph, urls)
        seen: set[str] = set()
        out: list[str] = []
        for u in urls:
            if u not in seen:
                seen.add(u)
                out.append(u)
        return out[:6]

    def _add_photo(self, photo: dict[str, Any], urls: list[str]) -> None:
        tmpl = (photo.get("upload") or {}).get("cropUrlTemplate")
        if tmpl and "%s" in tmpl:
            urls.append(tmpl.replace("%s", self.PHOTO_SIZE))
        elif tmpl:
            urls.append(tmpl)

    @staticmethod
    def _contact(offer: dict[str, Any]) -> dict[str, Any]:
        seller = offer.get("seller") or {}
        contact: dict[str, Any] = {}
        name = seller.get("name") or seller.get("title")
        if name:
            contact["name"] = name
        return contact

    @staticmethod
    def _apply_features(feats: list[Any], listing: RawListing) -> None:
        """Best-effort extraction of rooms/area from the detail features list."""
        for f in feats:
            if not isinstance(f, dict):
                continue
            label = (f.get("title") or f.get("name") or "").lower()
            value = str(f.get("value") or "")
            if listing.rooms is None and ("xona" in label or "комнат" in label or "room" in label):
                digits = "".join(ch for ch in value if ch.isdigit())
                if digits:
                    listing.rooms = int(digits)
            if listing.area is None and ("maydon" in label or "площад" in label or "area" in label or "m²" in value):
                digits = "".join(ch for ch in value if ch.isdigit())
                if digits:
                    listing.area = int(digits)
