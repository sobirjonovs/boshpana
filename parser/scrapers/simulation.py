"""Offline simulation scraper.

Generates ~15 realistic fake Tashkent rental listings with NO network access so
the full parse -> normalize -> ingest pipeline is demoable end to end.
"""
from __future__ import annotations

import random
from datetime import datetime, timedelta, timezone

from models import RawListing

from .base import BaseScraper

# Tashkent districts (tumanlar) + a representative metro station for each.
_DISTRICTS = [
    ("Chilonzor", "Chilonzor"),
    ("Yunusobod", "Minor"),
    ("Mirzo Ulug'bek", "Buyuk Ipak Yo'li"),
    ("Yakkasaroy", "Kosmonavtlar"),
    ("Shayxontohur", "Gafur Gulom"),
    ("Olmazor", "Olmazor"),
    ("Mirobod", "Oybek"),
    ("Sergeli", "Sergeli"),
    ("Yashnobod", "Mashinasozlar"),
    ("Bektemir", None),
]

_CONDITIONS = ["o'rtacha", "zo'r ta'mirli", "yangi ta'mir", "yevro remont"]
_FURNITURE = ["mebel bilan", "konditsioner, kir mashina", "jihozlangan", "mebelsiz"]
_EXTRAS = [
    "Yoshlar uchun qulay.",
    "Qizlar/ayollar uchun.",
    "Oilaga beriladi.",
    "Sherikchilikka ham mumkin.",
    "Metroga yaqin.",
    "Vositachilarsiz, to'g'ridan-to'g'ri egasidan.",
    "Maklerlik haqi 50%.",
    "Uzoq muddatga ijaraga.",
]
_NAMES = ["Aziz", "Dilnoza", "Bekzod", "Madina", "Sardor", "Nigora", "Jahongir", "Kamola"]


class SimulationScraper(BaseScraper):
    slug = "simulation"
    name = "Simulation"

    def __init__(self, source=None, count: int = 15, seed: int | None = None) -> None:
        super().__init__(source)
        self.count = count
        self._rng = random.Random(seed)

    async def fetch(self, since: datetime) -> list[RawListing]:
        now = datetime.now(timezone.utc)
        out: list[RawListing] = []
        for i in range(1, self.count + 1):
            out.append(self._make(i, now))
        # Respect the lookback window like a real scraper would.
        return [l for l in out if self.within_since(l.posted_at, since)]

    def _make(self, i: int, now: datetime) -> RawListing:
        rng = self._rng
        district, metro = rng.choice(_DISTRICTS)
        rooms = rng.choice([1, 1, 2, 2, 2, 3, 3, 4])
        area = rng.choice([28, 36, 42, 48, 55, 60, 72, 85])
        price = rng.choice([200, 250, 300, 350, 400, 450, 500, 600])
        condition = rng.choice(_CONDITIONS)
        furniture = rng.choice(_FURNITURE)
        extra = rng.choice(_EXTRAS)
        floor = rng.randint(1, 9)
        total = rng.randint(floor, 9)

        title = f"{rooms}-xonali kvartira, {district}, {area} m²"
        description = (
            f"{title}. {condition.capitalize()}, {furniture}. "
            f"{floor}/{total}-qavat. {extra} Narxi ${price}/oy."
        )
        posted_at = now - timedelta(hours=rng.randint(0, 23), minutes=rng.randint(0, 59))

        contact = {
            "phone": f"+99890{rng.randint(1000000, 9999999)}",
            "telegram": rng.choice(_NAMES).lower() + str(rng.randint(10, 99)),
        }

        return RawListing(
            external_id=f"sim-{i:03d}",
            url=f"https://example.test/sim/{i:03d}",
            title=title,
            description=description,
            price=price,
            currency="USD",
            images=[f"https://picsum.photos/seed/boshpana{i}/640/480"],
            contact=contact,
            rooms=rooms,
            area=area,
            address=f"Toshkent, {district} tumani",
            region_hint="Toshkent",
            posted_at=posted_at,
            raw={
                "source": self.slug,
                "district_hint": district,
                "metro_hint": metro,
                "condition_hint": condition,
            },
        )
