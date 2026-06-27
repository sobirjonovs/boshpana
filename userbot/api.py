"""Async HTTP client for the Boshpana.ai backend userbot-facing endpoints.

Mirrors CONTRACT.md section 3 (Userbot-facing, service.token:userbot):
  GET  negotiation/tasks
  POST negotiation/{conversation}/reply    {owner_message}
  POST negotiation/{conversation}/outcome  {outcome, summary?}
"""

from __future__ import annotations

from typing import Any

import httpx

from config import Config


class BackendApi:
    def __init__(self, config: Config) -> None:
        self._config = config
        self._client = httpx.AsyncClient(
            base_url=config.backend_url,
            headers={
                "Authorization": f"Bearer {config.userbot_token}",
                "Accept": "application/json",
            },
            timeout=30.0,
        )

    async def __aenter__(self) -> "BackendApi":
        return self

    async def __aexit__(self, *exc: Any) -> None:
        await self.close()

    async def close(self) -> None:
        await self._client.aclose()

    async def fetch_tasks(self) -> list[dict[str, Any]]:
        """Pending real-mode conversations needing first outreach.

        Each task: {conversation_id, listing:{id,title,contact}, opening_message, account_id?}.
        """
        resp = await self._client.get("/negotiation/tasks")
        resp.raise_for_status()
        return resp.json().get("data", [])

    async def reply(self, conversation_id: int, owner_message: str | None) -> dict[str, Any]:
        """Send the owner's latest message; get the next AI reply / completion.

        Returns {reply: str|None, done: bool, outcome?: agreed|declined|no_response}.
        """
        resp = await self._client.post(
            f"/negotiation/{conversation_id}/reply",
            json={"owner_message": owner_message},
        )
        resp.raise_for_status()
        return resp.json()

    async def outcome(
        self,
        conversation_id: int,
        outcome: str,
        summary: str | None = None,
    ) -> dict[str, Any]:
        """Report the final outcome of a negotiation. Returns {ok: true}."""
        payload: dict[str, Any] = {"outcome": outcome}
        if summary is not None:
            payload["summary"] = summary
        resp = await self._client.post(
            f"/negotiation/{conversation_id}/outcome",
            json=payload,
        )
        resp.raise_for_status()
        return resp.json()
