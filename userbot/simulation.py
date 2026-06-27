"""SIMULATION mode — local owner role-play, no Telegram required.

Pulls the same `negotiation/tasks`, then fabricates plausible apartment-owner
replies entirely offline and drives the identical reply/outcome endpoints so the
full negotiation flow is demoable with ZERO Telegram credentials.

The backend's OwnerNegotiator stays the brain: we only invent the owner's side of
the dialogue and forward each turn to `POST negotiation/{id}/reply`. When the
backend signals `done`, we report the final `POST negotiation/{id}/outcome`.
"""

from __future__ import annotations

import asyncio
import random

from api import BackendApi
from config import Config

# Canned owner turns, escalating from greeting toward a decision. Uzbek, since
# the primary content language is uz (CONTRACT). Plausible, varied, harmless.
_OPENING_REPLIES = [
    "Assalomu alaykum, ha kvartira hali bo'sh.",
    "Salom, qiziqyapsizmi? Ha, ijaraga beryapman.",
    "Ha eshitaman, nima savolingiz bor edi?",
]
_MIDDLE_REPLIES = [
    "Narxi kelishilgan, lekin biroz tushish mumkin.",
    "Mebel bor, konditsioner ham bor. Metroga yaqin.",
    "Yolg'iz yigit/qizlarga beraman, oilaga emas.",
    "Depozit bir oylik, kommunal alohida.",
    "Ko'rgani qachon kelasiz?",
]
_AGREE_REPLIES = [
    "Mayli, shartlaringiz menga to'g'ri keladi. Kelishdik.",
    "Yaxshi, roziman. Telefon raqamingizni yuboring.",
    "Bo'pti, men roziman, qachon ko'chib o'tasiz?",
]
_DECLINE_REPLIES = [
    "Yo'q, bu narxga bermayman, kechirasiz.",
    "Afsus, kvartira kecha berib yuborildi.",
    "Bu shartlar menga to'g'ri kelmaydi.",
]


def _owner_reply(turn: int, lean_agree: bool) -> str:
    if turn == 0:
        return random.choice(_OPENING_REPLIES)
    if turn < 3:
        return random.choice(_MIDDLE_REPLIES)
    return random.choice(_AGREE_REPLIES if lean_agree else _DECLINE_REPLIES)


async def _run_one(api: BackendApi, task: dict) -> None:
    conversation_id = task["conversation_id"]
    listing = task.get("listing") or {}
    title = listing.get("title", f"listing#{listing.get('id', '?')}")
    print(f"[sim] Owner role-play started for conversation {conversation_id} ({title}).")

    # 70% of simulated owners eventually agree, for a lively demo.
    lean_agree = random.random() < 0.7
    last_outcome = "no_response"

    for turn in range(8):  # hard cap so a stuck backend never loops forever
        owner_message = _owner_reply(turn, lean_agree)
        print(f"[sim]   owner -> {owner_message}")
        try:
            result = await api.reply(conversation_id, owner_message)
        except Exception as exc:  # noqa: BLE001
            print(f"[sim]   reply() failed: {exc}")
            return

        ai_reply = result.get("reply")
        if ai_reply:
            print(f"[sim]   ai    -> {ai_reply}")

        if result.get("done"):
            last_outcome = result.get("outcome") or last_outcome
            break

        await asyncio.sleep(0.4)

    try:
        await api.outcome(conversation_id, last_outcome, summary="Simulated negotiation.")
    except Exception as exc:  # noqa: BLE001
        print(f"[sim]   outcome() failed: {exc}")
        return

    print(f"[sim] Conversation {conversation_id} concluded: {last_outcome}.")


async def run_simulation(config: Config) -> None:
    print("[sim] Simulation mode — no Telegram credentials needed. "
          f"Polling {config.backend_url} every {config.poll_interval}s.")
    seen: set[int] = set()

    async with BackendApi(config) as api:
        while True:
            try:
                tasks = await api.fetch_tasks()
            except Exception as exc:  # noqa: BLE001
                print(f"[sim] Failed to fetch tasks: {exc}")
                await asyncio.sleep(config.poll_interval)
                continue

            fresh = [t for t in tasks if t.get("conversation_id") not in seen]
            for task in fresh:
                seen.add(task["conversation_id"])

            if fresh:
                await asyncio.gather(*(_run_one(api, t) for t in fresh))

            await asyncio.sleep(config.poll_interval)
