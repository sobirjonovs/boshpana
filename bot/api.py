"""Async HTTP client wrapping every bot-facing Boshpana.ai endpoint.

Mirrors CONTRACT.md section 3 (Bot-facing, service.token:bot). Every request
carries `Authorization: Bearer <BOT_API_TOKEN>`. Methods unwrap the `{data: ...}`
envelope and return plain dicts/lists.
"""

from __future__ import annotations

from typing import Any

import httpx

import config


class ApiError(Exception):
    """Raised when the backend returns a non-2xx response."""

    def __init__(self, status: int, body: Any) -> None:
        self.status = status
        self.body = body
        super().__init__(f"Backend error {status}: {body}")


class BackendApi:
    """Thin async wrapper around the Boshpana.ai REST API (v1)."""

    def __init__(
        self,
        base_url: str | None = None,
        token: str | None = None,
        timeout: float | None = None,
    ) -> None:
        self.base_url = (base_url or config.BACKEND_URL).rstrip("/")
        self.token = token or config.BOT_API_TOKEN
        self.timeout = timeout or config.HTTP_TIMEOUT
        self._client: httpx.AsyncClient | None = None

    # ----- lifecycle ----------------------------------------------------
    async def start(self) -> None:
        if self._client is None:
            self._client = httpx.AsyncClient(
                base_url=self.base_url,
                timeout=self.timeout,
                headers={
                    "Authorization": f"Bearer {self.token}",
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
            )

    async def close(self) -> None:
        if self._client is not None:
            await self._client.aclose()
            self._client = None

    @property
    def client(self) -> httpx.AsyncClient:
        if self._client is None:
            raise RuntimeError("BackendApi.start() must be called before use.")
        return self._client

    # ----- low-level ----------------------------------------------------
    async def _request(self, method: str, path: str, **kwargs: Any) -> Any:
        resp = await self.client.request(method, path, **kwargs)
        if resp.status_code >= 400:
            try:
                body = resp.json()
            except Exception:
                body = resp.text
            raise ApiError(resp.status_code, body)
        if resp.status_code == 204 or not resp.content:
            return None
        return resp.json()

    @staticmethod
    def _data(payload: Any) -> Any:
        if isinstance(payload, dict) and "data" in payload:
            return payload["data"]
        return payload

    # ----- Users --------------------------------------------------------
    async def sync_user(
        self,
        telegram_id: int,
        username: str | None = None,
        first_name: str | None = None,
        last_name: str | None = None,
        language: str | None = None,
    ) -> dict:
        body = {
            "telegram_id": telegram_id,
            "username": username,
            "first_name": first_name,
            "last_name": last_name,
            "language": language,
        }
        body = {k: v for k, v in body.items() if v is not None}
        return self._data(await self._request("POST", "/users/sync", json=body))

    async def get_user(self, telegram_id: int) -> dict:
        return self._data(await self._request("GET", f"/users/{telegram_id}"))

    async def update_user(self, telegram_id: int, **fields: Any) -> dict:
        body = {k: v for k, v in fields.items() if v is not None}
        return self._data(await self._request("PATCH", f"/users/{telegram_id}", json=body))

    # ----- Reference data ----------------------------------------------
    async def regions(self, lang: str = "uz") -> list[dict]:
        return self._data(await self._request("GET", "/regions", params={"lang": lang}))

    async def districts(self, region_id: int, lang: str = "uz") -> list[dict]:
        return self._data(
            await self._request(
                "GET", f"/regions/{region_id}/districts", params={"lang": lang}
            )
        )

    # ----- Search requests ---------------------------------------------
    async def create_search_request(self, telegram_id: int, **criteria: Any) -> dict:
        body = {"telegram_id": telegram_id}
        body.update({k: v for k, v in criteria.items() if v is not None})
        return self._data(await self._request("POST", "/search-requests", json=body))

    async def update_search_request(self, search_id: int, **criteria: Any) -> dict:
        body = {k: v for k, v in criteria.items() if v is not None}
        return self._data(
            await self._request("PATCH", f"/search-requests/{search_id}", json=body)
        )

    async def get_search_request(self, search_id: int) -> dict:
        return self._data(await self._request("GET", f"/search-requests/{search_id}"))

    async def start_search_request(self, search_id: int) -> dict:
        return self._data(
            await self._request("POST", f"/search-requests/{search_id}/start")
        )

    async def cancel_search_request(self, search_id: int) -> dict:
        return self._data(
            await self._request("POST", f"/search-requests/{search_id}/cancel")
        )

    async def search_results(self, search_id: int) -> list[dict]:
        return self._data(
            await self._request("GET", f"/search-requests/{search_id}/results")
        )

    # ----- Billing ------------------------------------------------------
    async def plans(self) -> list[dict]:
        return self._data(await self._request("GET", "/plans"))

    async def create_payment(self, telegram_id: int, plan_id: int, provider: str) -> dict:
        """Returns the raw envelope: {data: PaymentResource, pay_url?: str}."""
        body = {"telegram_id": telegram_id, "plan_id": plan_id, "provider": provider}
        return await self._request("POST", "/payments", json=body)

    async def get_payment(self, payment_id: int) -> dict:
        return self._data(await self._request("GET", f"/payments/{payment_id}"))


# A single shared client instance for the whole bot process.
api = BackendApi()
