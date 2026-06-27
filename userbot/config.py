"""Configuration for the Boshpana.ai userbot, read from the environment."""

from __future__ import annotations

import os
from dataclasses import dataclass

from dotenv import load_dotenv

load_dotenv()


def _int(name: str, default: int) -> int:
    raw = os.getenv(name, "").strip()
    if not raw:
        return default
    try:
        return int(raw)
    except ValueError:
        return default


def _id_list(name: str) -> tuple[str, ...]:
    raw = os.getenv(name, "")
    return tuple(part.strip() for part in raw.replace(";", ",").split(",") if part.strip())


@dataclass(frozen=True)
class Config:
    backend_url: str
    userbot_token: str
    tg_api_id: int | None
    tg_api_hash: str
    tg_string_session: str
    poll_interval: int
    # SAFETY: the userbot may ONLY message these Telegram ids / @usernames.
    # Real apartment owners are NEVER contacted.
    target_user_ids: tuple[str, ...]
    response_timeout: int

    @property
    def has_telegram_creds(self) -> bool:
        """True only when every credential needed for REAL mode is present."""
        return bool(self.tg_api_id and self.tg_api_hash and self.tg_string_session)

    def telegram_problem(self) -> str | None:
        """Human-readable explanation of which Telegram credentials are missing."""
        missing = []
        if not self.tg_api_id:
            missing.append("TG_API_ID")
        if not self.tg_api_hash:
            missing.append("TG_API_HASH")
        if not self.tg_string_session:
            missing.append("TG_STRING_SESSION")
        if not self.target_user_ids:
            missing.append("TARGET_USER_IDS")
        if not missing:
            return None
        return (
            "Missing for REAL mode: "
            + ", ".join(missing)
            + ". Set them in .env (TARGET_USER_IDS = the test accounts to talk to), "
            "or run with --simulation for a credential-free demo."
        )


def load_config() -> Config:
    return Config(
        backend_url=os.getenv("BACKEND_URL", "http://localhost:8000/api/v1").rstrip("/"),
        userbot_token=os.getenv("USERBOT_API_TOKEN", "local-userbot-token"),
        tg_api_id=_int("TG_API_ID", 0) or None,
        tg_api_hash=os.getenv("TG_API_HASH", "").strip(),
        tg_string_session=os.getenv("TG_STRING_SESSION", "").strip(),
        poll_interval=_int("POLL_INTERVAL", 5),
        target_user_ids=_id_list("TARGET_USER_IDS"),
        response_timeout=_int("RESPONSE_TIMEOUT", 180),
    )
