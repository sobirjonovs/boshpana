"""Entrypoint: builds the Application, wires handlers, runs long polling."""

from __future__ import annotations

import logging

from telegram.ext import Application, ApplicationBuilder

import bot
import config
from api import api

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)
log = logging.getLogger("boshpana.bot")


async def _post_init(app: Application) -> None:
    await api.start()
    log.info("Boshpana.ai bot started. Backend: %s", config.BACKEND_URL)


async def _post_shutdown(app: Application) -> None:
    await api.close()


def build_app() -> Application:
    config.validate()
    app = (
        ApplicationBuilder()
        .token(config.BOT_TOKEN)
        # Generous network timeouts + a bigger pool so sending replies to
        # Telegram does not intermittently time out under slow networks.
        .connect_timeout(30)
        .read_timeout(30)
        .write_timeout(30)
        .pool_timeout(30)
        .get_updates_read_timeout(45)
        .connection_pool_size(64)
        .post_init(_post_init)
        .post_shutdown(_post_shutdown)
        .build()
    )
    bot.register(app)
    return app


def main() -> None:
    app = build_app()
    app.run_polling(allowed_updates=["message", "callback_query"])


if __name__ == "__main__":
    main()
