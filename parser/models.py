"""Data models for the parser service.

`RawListing` mirrors the ingest RawListing shape from CONTRACT section 3:

    {external_id, url, title, description, price?, currency?, images?:[url],
     contact?:{phone,telegram}, rooms?, area?, address?, region_hint?,
     posted_at?(ISO8601), raw?:{}}
"""
from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, Optional


@dataclass
class RawListing:
    external_id: str
    url: str
    title: str
    description: str = ""
    price: Optional[int] = None
    currency: str = "USD"
    images: list[str] = field(default_factory=list)
    contact: dict[str, Any] = field(default_factory=dict)  # {phone?, telegram?}
    rooms: Optional[int] = None
    area: Optional[int] = None
    address: Optional[str] = None
    region_hint: Optional[str] = None
    posted_at: Optional[datetime] = None
    raw: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        """Serialize to the JSON payload expected by POST ingest/listings."""
        data: dict[str, Any] = {
            "external_id": str(self.external_id),
            "url": self.url,
            "title": self.title,
            "description": self.description or "",
            "currency": self.currency or "USD",
        }
        if self.price is not None:
            data["price"] = int(self.price)
        if self.images:
            data["images"] = list(self.images)
        if self.contact:
            data["contact"] = {k: v for k, v in self.contact.items() if v}
        if self.rooms is not None:
            data["rooms"] = int(self.rooms)
        if self.area is not None:
            data["area"] = int(self.area)
        if self.address:
            data["address"] = self.address
        if self.region_hint:
            data["region_hint"] = self.region_hint
        if self.posted_at is not None:
            data["posted_at"] = self.posted_at.isoformat()
        if self.raw:
            data["raw"] = self.raw
        return data
