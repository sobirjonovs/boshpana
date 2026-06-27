"""REAL-mode AI negotiator transport (Telethon) — SAFE test mode.

The backend is the brain (OwnerNegotiator / DeepSeek decides what to say). This
userbot is only the transport. To keep things safe while testing, it talks ONLY
to the Telegram accounts you whitelist in TARGET_USER_IDS — it NEVER messages the
real apartment owners from the listings. The listings stay simulated; only the
conversation is real.

Flow per task:
  1. GET negotiation/tasks → pending real-mode conversations.
  2. Pick a whitelisted target account (round-robin) — NOT the listing owner.
  3. Ask the backend for the AI opening (POST reply with no owner message) and
     send it to that target.
  4. On each incoming reply from that target → POST negotiation/{id}/reply →
     send the backend's AI reply back.
  5. When done (or after RESPONSE_TIMEOUT with no reply) → POST outcome and stop.
"""

from __future__ import annotations

import asyncio
from dataclasses import dataclass, field
from typing import Any

from api import BackendApi
from config import Config

try:
    from telethon import TelegramClient, events
    from telethon.sessions import StringSession

    _TELETHON_AVAILABLE = True
except ImportError:  # pragma: no cover - telethon is a runtime dependency
    TelegramClient = None  # type: ignore[assignment]
    events = None  # type: ignore[assignment]
    StringSession = None  # type: ignore[assignment]
    _TELETHON_AVAILABLE = False


@dataclass
class Negotiation:
    """One live conversation, keyed by the test account we are talking to."""

    conversation_id: int
    target_ref: str
    title: str
    done: bool = False
    history: list[str] = field(default_factory=list)
    timeout_task: Any = None


class UserbotRunner:
    def __init__(self, config: Config) -> None:
        self._config = config
        self._api = BackendApi(config)
        self._client: Any = None
        # target peer id -> Negotiation, so incoming replies route to the right task.
        self._by_target: dict[int, Negotiation] = {}
        # conversation_id -> Negotiation, to avoid starting a task twice.
        self._active: dict[int, Negotiation] = {}
        self._target_index = 0
        self._warned: set[str] = set()
        self._lock = asyncio.Lock()

    # -- lifecycle ---------------------------------------------------------

    def _ensure_ready(self) -> None:
        if not _TELETHON_AVAILABLE:
            raise RuntimeError(
                "Telethon is not installed. Run `pip install -r requirements.txt`, "
                "or use --simulation for a credential-free demo."
            )
        problem = self._config.telegram_problem()
        if problem:
            raise RuntimeError(problem)

    async def run(self) -> None:
        self._ensure_ready()

        self._client = TelegramClient(
            StringSession(self._config.tg_string_session),
            self._config.tg_api_id,
            self._config.tg_api_hash,
        )
        self._client.add_event_handler(
            self._on_target_message, events.NewMessage(incoming=True)
        )

        await self._client.start()
        me = await self._client.get_me()
        username = getattr(me, "username", None) or getattr(me, "id", "?")
        print(f"[userbot] Logged in as @{username}.")
        print(f"[userbot] SAFE mode — will ONLY message: {', '.join(self._config.target_user_ids)}")

        # Warm the entity cache so previously-seen numeric ids can be resolved.
        try:
            await self._client.get_dialogs()
        except Exception:  # noqa: BLE001
            pass

        print(f"[userbot] Polling every {self._config.poll_interval}s for negotiation tasks.")

        try:
            while True:
                try:
                    await self._poll_once()
                except Exception as exc:  # noqa: BLE001 - the loop must never die
                    print(f"[userbot] loop error (continuing): {exc!r}")
                await asyncio.sleep(self._config.poll_interval)
        finally:
            await self._api.close()
            await self._client.disconnect()

    # -- polling & outreach ------------------------------------------------

    async def _poll_once(self) -> None:
        try:
            tasks = await self._api.fetch_tasks()
        except Exception as exc:  # noqa: BLE001 - keep the loop alive
            print(f"[userbot] Failed to fetch tasks: {exc}")
            return
        for task in tasks:
            try:
                await self._start_task(task)
            except Exception as exc:  # noqa: BLE001 - one bad task must not kill the loop
                print(f"[userbot] start_task error for {task.get('conversation_id')}: {exc!r}")

    def _pick_target(self) -> str:
        targets = self._config.target_user_ids
        ref = targets[self._target_index % len(targets)]
        self._target_index += 1
        return ref

    async def _resolve(self, target_ref: str) -> Any:
        """Resolve a target to a Telethon entity.

        - "+998..." phone → imported as a contact (works if the number is on
          Telegram and allows being found by phone).
        - "@username"     → resolved directly (works cold).
        - numeric id      → only resolves if the account has 'seen' that user;
          we refresh dialogs once and retry.
        """
        ref = str(target_ref).strip()

        if ref.startswith("+") and ref[1:].replace(" ", "").isdigit():
            entity = await self._resolve_phone(ref)
            if entity is not None:
                return entity
        else:
            handle = _normalize_handle(ref)
            try:
                return await self._client.get_entity(handle)
            except Exception:  # noqa: BLE001
                pass
            try:
                await self._client.get_dialogs()
                return await self._client.get_entity(handle)
            except Exception as exc:  # noqa: BLE001
                if ref not in self._warned:
                    self._warned.add(ref)
                    print(f"[userbot] Cannot resolve target {ref}: {exc}")
                    if ref.lstrip("-").isdigit():
                        print("[userbot] TIP: a numeric id only resolves if that user "
                              "messaged this account before. Use the target's @username "
                              "or +phone number in TARGET_USER_IDS instead.")
                return None

        if ref not in self._warned:
            self._warned.add(ref)
            print(f"[userbot] Could not reach {ref} (not on Telegram, or their privacy "
                  "hides them from phone lookup).")
        return None

    async def _resolve_phone(self, phone: str) -> Any:
        """Add the phone as a contact and return its user entity, or None."""
        try:
            from telethon.tl.functions.contacts import ImportContactsRequest
            from telethon.tl.types import InputPhoneContact

            result = await self._client(ImportContactsRequest([
                InputPhoneContact(client_id=0, phone=phone,
                                  first_name="Boshpana", last_name="Test"),
            ]))
            if result.users:
                return result.users[0]
        except Exception as exc:  # noqa: BLE001
            print(f"[userbot] Phone import failed for {phone}: {exc}")
        return None

    async def _start_task(self, task: dict[str, Any]) -> None:
        conversation_id = task.get("conversation_id")
        if conversation_id is None or conversation_id in self._active:
            return

        # SAFETY: the counterparty is always a whitelisted test account, never
        # the listing's real owner contact (which we deliberately ignore).
        target_ref = self._pick_target()
        if target_ref not in self._config.target_user_ids:  # belt-and-braces
            print(f"[userbot] BLOCKED: {target_ref} is not whitelisted — skipping.")
            return

        entity = await self._resolve(target_ref)
        if entity is None:
            return

        listing = task.get("listing") or {}
        negotiation = Negotiation(
            conversation_id=conversation_id,
            target_ref=str(target_ref),
            title=listing.get("title", ""),
        )
        async with self._lock:
            self._active[conversation_id] = negotiation
            self._by_target[entity.id] = negotiation

        # Ask the backend (DeepSeek) for the opening line; fall back to the task one.
        opening = task.get("opening_message")
        try:
            result = await self._api.reply(conversation_id, None)
            opening = result.get("reply") or opening
        except Exception as exc:  # noqa: BLE001
            print(f"[userbot] opening reply() failed for {conversation_id}: {exc}")

        if not opening:
            await self._cleanup(entity.id, negotiation)
            return

        await self._client.send_message(entity, opening)
        negotiation.history.append(f"ai: {opening}")
        negotiation.timeout_task = asyncio.create_task(self._timeout(entity.id, negotiation))
        print(f"[userbot] Opened conversation {conversation_id} with {target_ref}.")

    # -- incoming target replies -------------------------------------------

    async def _on_target_message(self, event: Any) -> None:
        negotiation = self._by_target.get(event.sender_id)
        if negotiation is None or negotiation.done:
            return

        owner_message = (event.raw_text or "").strip()
        if not owner_message:
            return
        negotiation.history.append(f"owner: {owner_message}")

        try:
            result = await self._api.reply(negotiation.conversation_id, owner_message)
        except Exception as exc:  # noqa: BLE001
            print(f"[userbot] reply() failed for {negotiation.conversation_id}: {exc}")
            return

        reply_text = result.get("reply")
        if reply_text:
            await event.respond(reply_text)
            negotiation.history.append(f"ai: {reply_text}")

        if result.get("done"):
            await self._finish(event.sender_id, negotiation, result.get("outcome"))

    async def _timeout(self, target_peer_id: int, negotiation: Negotiation) -> None:
        try:
            await asyncio.sleep(self._config.response_timeout)
        except asyncio.CancelledError:
            return
        if not negotiation.done:
            print(f"[userbot] Conversation {negotiation.conversation_id} timed out.")
            await self._finish(target_peer_id, negotiation, "no_response")

    async def _finish(self, target_peer_id: int, negotiation: Negotiation, outcome: str | None) -> None:
        if negotiation.done:
            return
        negotiation.done = True
        if negotiation.timeout_task:
            negotiation.timeout_task.cancel()

        summary = " | ".join(negotiation.history[-6:])
        try:
            await self._api.outcome(negotiation.conversation_id, outcome or "no_response", summary)
        except Exception as exc:  # noqa: BLE001
            print(f"[userbot] outcome() failed for {negotiation.conversation_id}: {exc}")

        await self._cleanup(target_peer_id, negotiation)
        print(f"[userbot] Conversation {negotiation.conversation_id} finished: {outcome}.")

    async def _cleanup(self, target_peer_id: int, negotiation: Negotiation) -> None:
        async with self._lock:
            self._by_target.pop(target_peer_id, None)
            self._active.pop(negotiation.conversation_id, None)


def _normalize_handle(ref: str) -> str | int:
    ref = str(ref).strip()
    if ref.startswith("https://t.me/"):
        ref = ref.rsplit("/", 1)[-1]
    if ref.startswith("@"):
        return ref
    if ref.lstrip("-").isdigit():
        return int(ref)  # numeric Telegram id
    return f"@{ref}"


async def run_userbot(config: Config) -> None:
    await UserbotRunner(config).run()
