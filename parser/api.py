"""HTTP client for the backend ingest API.

Endpoints (CONTRACT section 3, parser-facing, auth = Bearer INGEST_API_TOKEN):
  - GET  ingest/sources   -> {data: [{id, slug, name, type, base_url, config}]}
  - POST ingest/listings  -> {created, updated, skipped}
"""
from __future__ import annotations

from typing import Any

import httpx

from config import config
from models import RawListing


class IngestApi:
    def __init__(self, base_url: str | None = None, token: str | None = None) -> None:
        self.base_url = (base_url or config.backend_url).rstrip("/")
        self.token = token or config.ingest_token

    def _client(self) -> httpx.Client:
        return httpx.Client(
            base_url=self.base_url,
            headers={
                "Authorization": f"Bearer {self.token}",
                "Accept": "application/json",
                "User-Agent": config.user_agent,
            },
            timeout=config.http_timeout,
        )

    def sources(self) -> list[dict[str, Any]]:
        """Return the list of active sources with their parser config."""
        try:
            with self._client() as client:
                resp = client.get("/ingest/sources")
                resp.raise_for_status()
                return resp.json().get("data", [])
        except (httpx.HTTPError, ValueError) as exc:
            print(f"[api] failed to fetch sources: {exc}")
            return []

    def push_listings(self, source_slug: str, listings: list[RawListing]) -> dict[str, int]:
        """POST normalized listings for a single source. Returns counts."""
        empty = {"created": 0, "updated": 0, "skipped": 0}
        if not listings:
            return empty
        payload = {
            "source": source_slug,
            "listings": [l.to_dict() for l in listings],
        }
        try:
            with self._client() as client:
                resp = client.post("/ingest/listings", json=payload)
                resp.raise_for_status()
                body = resp.json()
                return {
                    "created": int(body.get("created", 0)),
                    "updated": int(body.get("updated", 0)),
                    "skipped": int(body.get("skipped", 0)),
                }
        except (httpx.HTTPError, ValueError) as exc:
            print(f"[api] failed to push {len(listings)} listings for '{source_slug}': {exc}")
            return empty
