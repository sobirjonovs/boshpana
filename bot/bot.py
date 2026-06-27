"""Handlers for the Boshpana.ai user-facing Telegram bot.

Routing: inline taps are dispatched by their callback_data prefix (see
keyboards.CB_*). Free-text steps (price, area) are routed via user_data['step'].
The ordered search flow follows CONTRACT.md section 4 exactly.
"""

from __future__ import annotations

import asyncio
import logging
import re
from datetime import datetime

from telegram import Update
from telegram.constants import ParseMode
from telegram.error import BadRequest, TelegramError
from telegram.ext import (
    Application,
    CallbackQueryHandler,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)

import config
import keyboards as kb
from api import ApiError, api
from i18n import LANG_NAMES, t

log = logging.getLogger(__name__)

# Search-flow step markers stored in context.user_data['step'].
STEP_PRICE = "price"
STEP_AREA = "area"

SPINNER_FRAMES = ("⏳", "🔄", "⌛️", "🔃", "⏳", "🔁")

# Show at most this many (best-scored) results to the user.
MAX_RESULTS = 3


# ======================================================================
# Small helpers
# ======================================================================
def get_lang(context: ContextTypes.DEFAULT_TYPE) -> str:
    return context.user_data.get("lang") or config.DEFAULT_LANG


def criteria(context: ContextTypes.DEFAULT_TYPE) -> dict:
    return context.user_data.setdefault("criteria", {})


def opt_label(lang: str, kind: str, value: str | None) -> str:
    """Human label for an enum value, used in profile/summary fallbacks."""
    if value in (None, "", "any"):
        return t(lang, "opt_any")
    table = {
        "gender": {"male": "gender_male", "female": "gender_female"},
        "marital": {"single": "marital_single", "married": "marital_married"},
        "condition": {"average": "cond_average", "excellent": "cond_excellent"},
        "tri": {"yes": "opt_yes", "no": "opt_no"},
    }
    key = table.get(kind, {}).get(value)
    return t(lang, key) if key else value


def _name(value) -> str:
    """Region/district may arrive as a string or {id,name}."""
    if isinstance(value, dict):
        return value.get("name") or ""
    return value or ""


def parse_numbers(text: str) -> list[int]:
    return [int(n) for n in re.findall(r"\d+", text or "")]


# ======================================================================
# Onboarding & menu
# ======================================================================
async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    context.user_data.clear()
    tg = update.effective_user
    try:
        user = await api.sync_user(
            telegram_id=tg.id,
            username=tg.username,
            first_name=tg.first_name,
            last_name=tg.last_name,
        )
    except ApiError:
        log.exception("sync_user failed")
        await update.message.reply_text(t(config.DEFAULT_LANG, "error"))
        return

    lang = user.get("language")
    if lang:
        context.user_data["lang"] = lang
        await _send_greeting(update, context)
    else:
        # First run: ask for language.
        await update.message.reply_text(
            t(config.DEFAULT_LANG, "choose_language"),
            reply_markup=kb.language_keyboard(kb.CB_LANG),
        )


async def _send_greeting(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    chat = update.effective_chat
    await context.bot.send_message(
        chat.id, t(lang, "greeting"), parse_mode=ParseMode.HTML
    )
    await context.bot.send_message(
        chat.id, t(lang, "main_menu"), reply_markup=kb.main_menu(lang)
    )


async def on_first_language(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = q.data.split(":", 1)[1]
    context.user_data["lang"] = lang
    try:
        await api.update_user(update.effective_user.id, language=lang)
    except ApiError:
        log.exception("update_user language failed")
    await q.edit_message_text(t(lang, "language_set"))
    await _send_greeting(update, context)


async def show_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    q = update.callback_query
    if q:
        await q.answer()
        try:
            await q.edit_message_text(t(lang, "main_menu"), reply_markup=kb.main_menu(lang))
            return
        except BadRequest:
            pass
    await context.bot.send_message(
        update.effective_chat.id, t(lang, "main_menu"), reply_markup=kb.main_menu(lang)
    )


# ======================================================================
# Main menu router
# ======================================================================
async def on_menu(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    action = q.data.split(":", 1)[1]
    if action == "search":
        await begin_search(update, context)
    elif action == "pay":
        await show_plans(update, context)
    elif action == "settings":
        await show_settings(update, context)


async def on_nav(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    await show_menu(update, context)


# ======================================================================
# Settings
# ======================================================================
async def show_settings(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    q = update.callback_query
    user = {}
    try:
        user = await api.get_user(update.effective_user.id)
    except ApiError:
        pass
    text = t(lang, "settings_title") + "\n\n" + t(
        lang,
        "profile_line",
        lang_name=LANG_NAMES.get(lang, lang),
        gender=opt_label(lang, "gender", user.get("gender")),
        marital=opt_label(lang, "marital", user.get("marital_status")),
    )
    await q.edit_message_text(text, reply_markup=kb.settings_keyboard(lang), parse_mode=ParseMode.HTML)


async def on_settings_action(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    action = q.data.split(":", 1)[1]
    if action == "lang":
        await q.edit_message_text(
            t(lang, "choose_language"), reply_markup=kb.language_keyboard(kb.CB_SLANG)
        )
    elif action == "gender":
        await q.edit_message_text(
            t(lang, "choose_gender"), reply_markup=kb.gender_keyboard(lang, kb.CB_SGENDER)
        )
    elif action == "marital":
        await q.edit_message_text(
            t(lang, "choose_marital"), reply_markup=kb.marital_keyboard(lang, kb.CB_SMARITAL)
        )


async def on_settings_language(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = q.data.split(":", 1)[1]
    context.user_data["lang"] = lang
    try:
        await api.update_user(update.effective_user.id, language=lang)
    except ApiError:
        log.exception("update_user language failed")
    await q.edit_message_text(t(lang, "language_set"), reply_markup=kb.menu_button(lang))


async def on_profile_gender(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    value = q.data.split(":", 1)[1]
    try:
        await api.update_user(update.effective_user.id, gender=value)
    except ApiError:
        log.exception("update_user gender failed")
    await q.edit_message_text(t(lang, "saved"), reply_markup=kb.menu_button(lang))


async def on_profile_marital(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    value = q.data.split(":", 1)[1]
    try:
        await api.update_user(update.effective_user.id, marital_status=value)
    except ApiError:
        log.exception("update_user marital failed")
    await q.edit_message_text(t(lang, "saved"), reply_markup=kb.menu_button(lang))


# ======================================================================
# Payment
# ======================================================================
async def show_plans(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    q = update.callback_query
    try:
        user = await api.get_user(update.effective_user.id)
        plans = await api.plans()
    except ApiError:
        log.exception("plans failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return
    if not plans:
        await q.edit_message_text(t(lang, "no_plans"), reply_markup=kb.menu_button(lang))
        return
    header = t(
        lang,
        "payment_title",
        balance=user.get("balance", 0),
        free=user.get("free_searches_left", 0),
    )
    await q.edit_message_text(
        header + "\n\n" + t(lang, "choose_plan"),
        reply_markup=kb.plans_keyboard(lang, plans),
        parse_mode=ParseMode.HTML,
    )


async def on_plan(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    plan_id = int(q.data.split(":", 1)[1])
    name, price = str(plan_id), ""
    try:
        for p in await api.plans():
            if p["id"] == plan_id:
                name = p.get("name", str(plan_id))
                price = kb.format_price(p.get("price"), p.get("currency", "UZS"),
                                        p.get("period_days"), lang)
                break
    except ApiError:
        pass
    await q.edit_message_text(
        t(lang, "choose_provider", plan=name, price=price),
        reply_markup=kb.providers_keyboard(lang, plan_id),
        parse_mode=ParseMode.HTML,
    )


async def on_pay(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    _, plan_id, provider = q.data.split(":", 2)
    try:
        envelope = await api.create_payment(
            telegram_id=update.effective_user.id, plan_id=int(plan_id), provider=provider
        )
    except ApiError:
        log.exception("create_payment failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return
    payment = envelope.get("data", {}) if isinstance(envelope, dict) else {}
    pay_url = envelope.get("pay_url") if isinstance(envelope, dict) else None
    text = t(
        lang,
        "payment_created",
        plan=payment.get("plan", {}).get("name") if isinstance(payment.get("plan"), dict) else plan_id,
        amount=kb.format_price(payment.get("amount"), payment.get("currency", "UZS"), None, lang),
        status=payment.get("status", "pending"),
    )
    if pay_url:
        await q.edit_message_text(
            text, reply_markup=kb.pay_link_keyboard(lang, pay_url), parse_mode=ParseMode.HTML
        )
    else:
        await q.edit_message_text(
            text + "\n\n" + t(lang, "no_pay_url", status=payment.get("status", "pending")),
            reply_markup=kb.menu_button(lang),
            parse_mode=ParseMode.HTML,
        )


# ======================================================================
# Search flow (CONTRACT section 4)
# ======================================================================
async def begin_search(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    context.user_data["criteria"] = {}
    context.user_data["rooms"] = set()
    context.user_data.pop("step", None)
    q = update.callback_query
    try:
        regions = await api.regions(lang)
    except ApiError:
        log.exception("regions failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return
    await q.edit_message_text(t(lang, "step_region"), reply_markup=kb.regions_keyboard(regions))


async def on_region(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    region_id = int(q.data.split(":", 1)[1])
    criteria(context)["region_id"] = region_id
    try:
        districts = await api.districts(region_id, lang)
    except ApiError:
        log.exception("districts failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return
    if not districts:
        # Region without districts: skip straight to price.
        await q.edit_message_text(t(lang, "step_price"), parse_mode=ParseMode.HTML)
        context.user_data["step"] = STEP_PRICE
        return
    await q.edit_message_text(t(lang, "step_district"), reply_markup=kb.districts_keyboard(districts))


async def on_district(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["district_id"] = int(q.data.split(":", 1)[1])
    context.user_data["step"] = STEP_PRICE
    await q.edit_message_text(t(lang, "step_price"), parse_mode=ParseMode.HTML)


async def on_text(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    """Routes free-text steps (price, area)."""
    step = context.user_data.get("step")
    lang = get_lang(context)
    if step == STEP_PRICE:
        nums = parse_numbers(update.message.text)
        if not nums:
            await update.message.reply_text(t(lang, "price_invalid"))
            return
        if len(nums) == 1:
            criteria(context)["price_max"] = nums[0]
        else:
            lo, hi = sorted(nums[:2])
            criteria(context)["price_min"], criteria(context)["price_max"] = lo, hi
        context.user_data.pop("step", None)
        await _ask_rooms(update, context)
    elif step == STEP_AREA:
        nums = parse_numbers(update.message.text)
        if not nums:
            await update.message.reply_text(t(lang, "area_invalid"))
            return
        if len(nums) == 1:
            criteria(context)["area_min"] = nums[0]
        else:
            lo, hi = sorted(nums[:2])
            criteria(context)["area_min"], criteria(context)["area_max"] = lo, hi
        context.user_data.pop("step", None)
        await _ask_mode(update, context)
    else:
        await update.message.reply_text(t(lang, "session_expired"))


async def _ask_rooms(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    selected: set[int] = context.user_data.setdefault("rooms", set())
    await update.effective_chat.send_message(
        t(lang, "step_rooms"), reply_markup=kb.rooms_keyboard(lang, selected)
    )


async def on_room_toggle(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    lang = get_lang(context)
    n = int(q.data.split(":", 1)[1])
    selected: set[int] = context.user_data.setdefault("rooms", set())
    if n in selected:
        selected.discard(n)
    else:
        selected.add(n)
    await q.answer()
    try:
        await q.edit_message_reply_markup(reply_markup=kb.rooms_keyboard(lang, selected))
    except BadRequest:
        pass


async def on_rooms_done(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    selected = sorted(context.user_data.get("rooms", set()))
    if selected:
        criteria(context)["rooms"] = selected
    await q.edit_message_text(t(lang, "step_condition"), reply_markup=kb.condition_keyboard(lang))


async def on_condition(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["condition"] = q.data.split(":", 1)[1]
    await q.edit_message_text(t(lang, "step_furniture"), reply_markup=kb.furniture_keyboard(lang))


async def on_furniture(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["has_furniture"] = q.data.split(":", 1)[1]
    await q.edit_message_text(t(lang, "step_commission"), reply_markup=kb.commission_keyboard(lang))


async def on_commission(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["has_commission"] = q.data.split(":", 1)[1]
    context.user_data["step"] = STEP_AREA
    await q.edit_message_text(t(lang, "step_area"), reply_markup=kb.area_keyboard(lang), parse_mode=ParseMode.HTML)


async def on_area_skip(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    context.user_data.pop("step", None)
    await _ask_mode(update, context, via_query=q)


async def _ask_mode(update: Update, context: ContextTypes.DEFAULT_TYPE, via_query=None) -> None:
    lang = get_lang(context)
    if via_query is not None:
        await via_query.edit_message_text(t(lang, "step_mode"), reply_markup=kb.mode_keyboard(lang))
    else:
        await update.effective_chat.send_message(
            t(lang, "step_mode"), reply_markup=kb.mode_keyboard(lang)
        )


async def on_mode(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    mode = q.data.split(":", 1)[1]
    criteria(context)["mode"] = mode
    if mode == "partnership":
        await q.edit_message_text(t(lang, "step_partners"), reply_markup=kb.partners_keyboard(lang))
    else:
        criteria(context).pop("partners_count", None)
        await q.edit_message_text(t(lang, "step_metro"), reply_markup=kb.metro_keyboard(lang))


async def on_partners(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["partners_count"] = int(q.data.split(":", 1)[1])
    await q.edit_message_text(t(lang, "step_metro"), reply_markup=kb.metro_keyboard(lang))


async def on_metro(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["near_metro"] = q.data.split(":", 1)[1]
    await q.edit_message_text(t(lang, "step_gender"), reply_markup=kb.gender_req_keyboard(lang))


async def on_gender(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["gender"] = q.data.split(":", 1)[1]
    await q.edit_message_text(t(lang, "step_marital"), reply_markup=kb.marital_req_keyboard(lang))


async def on_marital(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    criteria(context)["marital_status"] = q.data.split(":", 1)[1]
    await _show_confirmation(update, context)


async def _show_confirmation(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    lang = get_lang(context)
    q = update.callback_query
    try:
        sr = await api.create_search_request(
            telegram_id=update.effective_user.id, **criteria(context)
        )
    except ApiError:
        log.exception("create_search_request failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return
    context.user_data["search_id"] = sr["id"]
    summary = sr.get("summary") or _local_summary(lang, context)
    await q.edit_message_text(
        t(lang, "step_confirm", summary=summary),
        reply_markup=kb.confirm_keyboard(lang),
        parse_mode=ParseMode.HTML,
    )


def _local_summary(lang: str, context: ContextTypes.DEFAULT_TYPE) -> str:
    """Fallback summary if the backend resource omits one."""
    c = criteria(context)
    parts = []
    if c.get("price_max") or c.get("price_min"):
        parts.append(f"💵 {c.get('price_min', '')}-{c.get('price_max', '')}$".replace("-$", "$"))
    if c.get("rooms"):
        parts.append("🚪 " + "/".join(map(str, c["rooms"])))
    parts.append("🙂 " + opt_label(lang, "condition", c.get("condition")))
    return "\n".join(parts) if parts else t(lang, "none")


async def on_confirm(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    q = update.callback_query
    await q.answer()
    lang = get_lang(context)
    action = q.data.split(":", 1)[1]
    if action == "edit":
        await begin_search(update, context)
        return

    search_id = context.user_data.get("search_id")
    if not search_id:
        await q.edit_message_text(t(lang, "session_expired"), reply_markup=kb.menu_button(lang))
        return

    # Quota check.
    try:
        user = await api.get_user(update.effective_user.id)
        if not user.get("can_search", True):
            await q.edit_message_text(t(lang, "cannot_search"), reply_markup=kb.menu_button(lang))
            return
        await api.start_search_request(search_id)
    except ApiError:
        log.exception("start_search_request failed")
        await q.edit_message_text(t(lang, "error"), reply_markup=kb.menu_button(lang))
        return

    await q.edit_message_text(t(lang, "search_starting"))
    chat_id = update.effective_chat.id
    context.application.create_task(run_poller(context, chat_id, lang, search_id))


# ======================================================================
# Progress poller
# ======================================================================
async def run_poller(
    context: ContextTypes.DEFAULT_TYPE, chat_id: int, lang: str, search_id: int
) -> None:
    msg = await context.bot.send_message(
        chat_id, t(lang, "progress", spinner=SPINNER_FRAMES[0], progress=0,
                   scanned=0, contacted=0, agreed=0, time=datetime.now().strftime("%H:%M:%S")),
        parse_mode=ParseMode.HTML,
    )
    frame = 0
    last_text = ""

    while True:
        await asyncio.sleep(config.POLL_INTERVAL)
        frame += 1
        try:
            sr = await api.get_search_request(search_id)
        except ApiError:
            continue

        status = sr.get("status", "searching")

        # No per-match "1 ta topildi!" pings — only the final results are shown.
        text = t(
            lang,
            "progress",
            spinner=SPINNER_FRAMES[frame % len(SPINNER_FRAMES)],
            progress=sr.get("progress", 0),
            scanned=sr.get("scanned_count", 0),
            contacted=sr.get("contacted_count", 0),
            agreed=sr.get("agreed_count", 0),
            time=datetime.now().strftime("%H:%M:%S"),
        )
        if text != last_text:
            try:
                await context.bot.edit_message_text(
                    text, chat_id=chat_id, message_id=msg.message_id, parse_mode=ParseMode.HTML
                )
                last_text = text
            except BadRequest:
                pass
            except TelegramError:
                pass

        if status in ("completed", "cancelled", "failed"):
            await _finish_search(context, chat_id, lang, search_id, sr, status)
            return


async def _finish_search(context, chat_id: int, lang: str, search_id: int, sr: dict, status: str) -> None:
    if status == "cancelled":
        await context.bot.send_message(
            chat_id, t(lang, "search_cancelled"), reply_markup=kb.menu_button(lang)
        )
        return
    if status == "failed":
        await context.bot.send_message(
            chat_id, t(lang, "error"), reply_markup=kb.menu_button(lang)
        )
        return

    await context.bot.send_message(
        chat_id,
        t(lang, "search_done", agreed=sr.get("agreed_count", 0), scanned=sr.get("scanned_count", 0)),
        parse_mode=ParseMode.HTML,
    )
    try:
        results = await api.search_results(search_id)
    except ApiError:
        results = []
    agreed = [m for m in results if m.get("status") == "agreed"]
    if not agreed:
        await context.bot.send_message(
            chat_id, t(lang, "no_results"), reply_markup=kb.menu_button(lang)
        )
        return
    # Best-scored first, show at most MAX_RESULTS.
    agreed.sort(key=lambda m: m.get("score", 0), reverse=True)
    for m in agreed[:MAX_RESULTS]:
        await context.bot.send_message(
            chat_id, _result_card(lang, m), parse_mode=ParseMode.HTML, disable_web_page_preview=True
        )
    await context.bot.send_message(
        chat_id, t(lang, "main_menu"), reply_markup=kb.main_menu(lang)
    )


def _result_card(lang: str, match: dict) -> str:
    listing = match.get("listing", {}) or {}
    area = listing.get("area")
    area_str = f" · 📐 {area} m²" if area else ""
    source = listing.get("source", {})
    source_name = source.get("name") if isinstance(source, dict) else (source or "")
    return t(
        lang,
        "result_card",
        title=listing.get("title", "—"),
        price=listing.get("price", "—"),
        rooms=listing.get("rooms", "—"),
        area=area_str,
        district=_name(listing.get("district")) or _name(listing.get("region")),
        condition=opt_label(lang, "condition", str(listing.get("condition")) if listing.get("condition") else "any"),
        source=source_name,
        link=listing.get("url", ""),
    )


# ======================================================================
# Errors & registration
# ======================================================================
async def on_error(update: object, context: ContextTypes.DEFAULT_TYPE) -> None:
    log.error("Unhandled error", exc_info=context.error)


def register(app: Application) -> None:
    app.add_handler(CommandHandler("start", start))
    app.add_handler(CommandHandler("menu", show_menu))

    # Language pickers.
    app.add_handler(CallbackQueryHandler(on_first_language, pattern=rf"^{kb.CB_LANG}:"))
    app.add_handler(CallbackQueryHandler(on_settings_language, pattern=rf"^{kb.CB_SLANG}:"))

    # Menu & navigation.
    app.add_handler(CallbackQueryHandler(on_menu, pattern=rf"^{kb.CB_MENU}:"))
    app.add_handler(CallbackQueryHandler(on_nav, pattern=rf"^{kb.CB_NAV}:"))

    # Settings.
    app.add_handler(CallbackQueryHandler(on_settings_action, pattern=rf"^{kb.CB_SET}:"))
    app.add_handler(CallbackQueryHandler(on_profile_gender, pattern=rf"^{kb.CB_SGENDER}:"))
    app.add_handler(CallbackQueryHandler(on_profile_marital, pattern=rf"^{kb.CB_SMARITAL}:"))

    # Billing.
    app.add_handler(CallbackQueryHandler(on_plan, pattern=rf"^{kb.CB_PLAN}:"))
    app.add_handler(CallbackQueryHandler(on_pay, pattern=rf"^{kb.CB_PAY}:"))

    # Search flow.
    app.add_handler(CallbackQueryHandler(on_region, pattern=rf"^{kb.CB_REGION}:"))
    app.add_handler(CallbackQueryHandler(on_district, pattern=rf"^{kb.CB_DISTRICT}:"))
    app.add_handler(CallbackQueryHandler(on_room_toggle, pattern=rf"^{kb.CB_ROOM}:"))
    app.add_handler(CallbackQueryHandler(on_rooms_done, pattern=rf"^{kb.CB_ROOMS_DONE}:"))
    app.add_handler(CallbackQueryHandler(on_condition, pattern=rf"^{kb.CB_COND}:"))
    app.add_handler(CallbackQueryHandler(on_furniture, pattern=rf"^{kb.CB_FURN}:"))
    app.add_handler(CallbackQueryHandler(on_commission, pattern=rf"^{kb.CB_COMM}:"))
    app.add_handler(CallbackQueryHandler(on_area_skip, pattern=rf"^{kb.CB_AREA}:"))
    app.add_handler(CallbackQueryHandler(on_mode, pattern=rf"^{kb.CB_MODE}:"))
    app.add_handler(CallbackQueryHandler(on_partners, pattern=rf"^{kb.CB_PARTNERS}:"))
    app.add_handler(CallbackQueryHandler(on_metro, pattern=rf"^{kb.CB_METRO}:"))
    app.add_handler(CallbackQueryHandler(on_gender, pattern=rf"^{kb.CB_GENDER}:"))
    app.add_handler(CallbackQueryHandler(on_marital, pattern=rf"^{kb.CB_MARITAL}:"))
    app.add_handler(CallbackQueryHandler(on_confirm, pattern=rf"^{kb.CB_CONFIRM}:"))

    # Free-text steps (price, area).
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, on_text))

    app.add_error_handler(on_error)
