"""Entry point for the Boshpana.ai AI negotiator userbot.

  python main.py               # REAL mode (Telethon, needs TG_* credentials)
  python main.py --simulation  # local owner role-play, zero Telegram credentials
"""

from __future__ import annotations

import argparse
import asyncio
import sys

from config import load_config


def parse_args(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Boshpana.ai AI negotiator userbot.")
    parser.add_argument(
        "--simulation",
        action="store_true",
        help="Role-play apartment owners locally without any Telegram account.",
    )
    return parser.parse_args(argv)


async def _async_main(simulation: bool) -> None:
    config = load_config()

    if simulation:
        from simulation import run_simulation

        await run_simulation(config)
        return

    if not config.has_telegram_creds:
        problem = config.telegram_problem()
        print(f"[userbot] {problem}", file=sys.stderr)
        sys.exit(1)

    from userbot import run_userbot

    await run_userbot(config)


def main() -> None:
    args = parse_args()
    # Survive a closed/detached terminal (SIGHUP) so it can run as a background service.
    try:
        import signal

        signal.signal(signal.SIGHUP, signal.SIG_IGN)
    except (ImportError, ValueError, OSError, AttributeError):
        pass
    try:
        asyncio.run(_async_main(args.simulation))
    except KeyboardInterrupt:
        print("\n[userbot] Stopped.")


if __name__ == "__main__":
    main()
