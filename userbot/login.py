"""One-time Telegram login → prints a StringSession string.

Telethon doesn't use a username/password to "set an account" — you log in once
with your phone number + the code Telegram sends you (and your 2FA password, if
you have one). That produces a StringSession you paste into your .env files.

USAGE
-----
1) Get API credentials at https://my.telegram.org → "API development tools"
   → create an app → copy the *App api_id* and *App api_hash*.
   Put them in userbot/.env:
       TG_API_ID=1234567
       TG_API_HASH=abcd1234ef...

2) Install deps and run this once:
       cd userbot
       python -m venv .venv && source .venv/bin/activate
       pip install -r requirements.txt
       python login.py

   It will ask for your phone number (e.g. +99890...), the login code Telegram
   sends to that account, and your 2FA password if one is set.

3) Copy the printed session string into BOTH .env files:
       userbot/.env  →  TG_STRING_SESSION=<the string>
       parser/.env   →  TG_SESSION=<the string>   (same string; the parser's
                                                    Telegram scraper reuses it)

Keep the session string SECRET — it grants full access to your account.
Use a dedicated/secondary number for bots, not your personal one.
"""
from __future__ import annotations

import os

try:
    from dotenv import load_dotenv

    load_dotenv()
except ImportError:
    pass

from telethon.sync import TelegramClient
from telethon.sessions import StringSession


def main() -> None:
    api_id = os.getenv("TG_API_ID") or input("API ID: ").strip()
    api_hash = os.getenv("TG_API_HASH") or input("API Hash: ").strip()

    # Used as a context manager, the sync client calls .start() for you, which
    # interactively prompts for phone number → code → 2FA password.
    with TelegramClient(StringSession(), int(api_id), api_hash) as client:
        me = client.get_me()
        handle = f" (@{me.username})" if me.username else ""
        print()
        print(f"✅ Logged in as: {me.first_name}{handle}")
        print()
        print("Copy this into userbot/.env (TG_STRING_SESSION) and parser/.env (TG_SESSION):")
        print()
        print(client.session.save())
        print()


if __name__ == "__main__":
    main()
