"""Inline keyboard builders. Every button carries an emoji and a routed
callback_data of the form `<prefix>:<value>` (see bot.py CB_* constants)."""

from __future__ import annotations

from telegram import InlineKeyboardButton, InlineKeyboardMarkup

import config
from i18n import LANG_NAMES, t

# Callback-data prefixes (kept in sync with handlers in bot.py).
CB_LANG = "lang"          # first-run language pick
CB_SLANG = "slang"        # settings language pick
CB_MENU = "menu"          # main menu actions
CB_SET = "set"            # settings sub-actions
CB_SGENDER = "sgender"    # profile gender
CB_SMARITAL = "smarital"  # profile marital
CB_REGION = "region"
CB_DISTRICT = "district"
CB_ROOM = "room"
CB_ROOMS_DONE = "roomsdone"
CB_COND = "cond"
CB_FURN = "furn"
CB_COMM = "comm"
CB_AREA = "area"
CB_MODE = "mode"
CB_PARTNERS = "partners"
CB_METRO = "metro"
CB_GENDER = "gender"
CB_MARITAL = "marital"
CB_CONFIRM = "confirm"
CB_PLAN = "plan"
CB_PAY = "pay"
CB_NAV = "nav"            # generic navigation (menu/back)


def _rows(buttons: list[InlineKeyboardButton], per_row: int) -> list[list[InlineKeyboardButton]]:
    return [buttons[i : i + per_row] for i in range(0, len(buttons), per_row)]


# ----- language ---------------------------------------------------------
def language_keyboard(prefix: str = CB_LANG) -> InlineKeyboardMarkup:
    rows = [
        [InlineKeyboardButton(LANG_NAMES[code], callback_data=f"{prefix}:{code}")]
        for code in config.SUPPORTED_LANGS
    ]
    return InlineKeyboardMarkup(rows)


# ----- main menu --------------------------------------------------------
def main_menu(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [InlineKeyboardButton(t(lang, "btn_search"), callback_data=f"{CB_MENU}:search")],
            [
                InlineKeyboardButton(t(lang, "btn_payment"), callback_data=f"{CB_MENU}:pay"),
                InlineKeyboardButton(t(lang, "btn_settings"), callback_data=f"{CB_MENU}:settings"),
            ],
        ]
    )


def menu_button(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [[InlineKeyboardButton(t(lang, "btn_menu"), callback_data=f"{CB_NAV}:menu")]]
    )


# ----- settings ---------------------------------------------------------
def settings_keyboard(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [InlineKeyboardButton(t(lang, "btn_change_language"), callback_data=f"{CB_SET}:lang")],
            [
                InlineKeyboardButton(t(lang, "btn_set_gender"), callback_data=f"{CB_SET}:gender"),
                InlineKeyboardButton(t(lang, "btn_set_marital"), callback_data=f"{CB_SET}:marital"),
            ],
            [InlineKeyboardButton(t(lang, "btn_menu"), callback_data=f"{CB_NAV}:menu")],
        ]
    )


def gender_keyboard(lang: str, prefix: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton(t(lang, "gender_male"), callback_data=f"{prefix}:male"),
                InlineKeyboardButton(t(lang, "gender_female"), callback_data=f"{prefix}:female"),
            ],
            [InlineKeyboardButton(t(lang, "opt_any"), callback_data=f"{prefix}:any")],
        ]
    )


def marital_keyboard(lang: str, prefix: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton(t(lang, "marital_single"), callback_data=f"{prefix}:single"),
                InlineKeyboardButton(t(lang, "marital_married"), callback_data=f"{prefix}:married"),
            ],
            [InlineKeyboardButton(t(lang, "opt_any"), callback_data=f"{prefix}:any")],
        ]
    )


# ----- search flow ------------------------------------------------------
def regions_keyboard(regions: list[dict]) -> InlineKeyboardMarkup:
    buttons = [
        InlineKeyboardButton(f"📍 {r['name']}", callback_data=f"{CB_REGION}:{r['id']}")
        for r in regions
    ]
    return InlineKeyboardMarkup(_rows(buttons, 2))


def districts_keyboard(districts: list[dict]) -> InlineKeyboardMarkup:
    buttons = []
    for d in districts:
        metro = " 🚇" if d.get("has_metro") else ""
        buttons.append(
            InlineKeyboardButton(f"🏙 {d['name']}{metro}", callback_data=f"{CB_DISTRICT}:{d['id']}")
        )
    return InlineKeyboardMarkup(_rows(buttons, 2))


def rooms_keyboard(lang: str, selected: set[int]) -> InlineKeyboardMarkup:
    buttons = []
    for n in (1, 2, 3, 4, 5):
        mark = "✅ " if n in selected else ""
        buttons.append(
            InlineKeyboardButton(f"{mark}{t(lang, 'room_label', n=n)}", callback_data=f"{CB_ROOM}:{n}")
        )
    rows = _rows(buttons, 5)
    rows.append([InlineKeyboardButton(t(lang, "btn_continue"), callback_data=f"{CB_ROOMS_DONE}:1")])
    return InlineKeyboardMarkup(rows)


def condition_keyboard(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton(t(lang, "cond_average"), callback_data=f"{CB_COND}:average"),
                InlineKeyboardButton(t(lang, "cond_excellent"), callback_data=f"{CB_COND}:excellent"),
            ],
            [InlineKeyboardButton(t(lang, "opt_any"), callback_data=f"{CB_COND}:any")],
        ]
    )


def _tristate_keyboard(lang: str, prefix: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [
                InlineKeyboardButton(t(lang, "opt_yes"), callback_data=f"{prefix}:yes"),
                InlineKeyboardButton(t(lang, "opt_no"), callback_data=f"{prefix}:no"),
            ],
            [InlineKeyboardButton(t(lang, "opt_any"), callback_data=f"{prefix}:any")],
        ]
    )


def furniture_keyboard(lang: str) -> InlineKeyboardMarkup:
    return _tristate_keyboard(lang, CB_FURN)


def commission_keyboard(lang: str) -> InlineKeyboardMarkup:
    return _tristate_keyboard(lang, CB_COMM)


def metro_keyboard(lang: str) -> InlineKeyboardMarkup:
    return _tristate_keyboard(lang, CB_METRO)


def area_keyboard(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [[InlineKeyboardButton(t(lang, "btn_skip"), callback_data=f"{CB_AREA}:skip")]]
    )


def mode_keyboard(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [InlineKeyboardButton(t(lang, "mode_solo"), callback_data=f"{CB_MODE}:solo")],
            [InlineKeyboardButton(t(lang, "mode_partnership"), callback_data=f"{CB_MODE}:partnership")],
        ]
    )


def partners_keyboard(lang: str) -> InlineKeyboardMarkup:
    buttons = [InlineKeyboardButton(t(lang, "partners_self"), callback_data=f"{CB_PARTNERS}:1")]
    for n in (2, 3, 4):
        buttons.append(InlineKeyboardButton(f"👥 {n}", callback_data=f"{CB_PARTNERS}:{n}"))
    return InlineKeyboardMarkup(_rows(buttons, 4))


def gender_req_keyboard(lang: str) -> InlineKeyboardMarkup:
    return gender_keyboard(lang, CB_GENDER)


def marital_req_keyboard(lang: str) -> InlineKeyboardMarkup:
    return marital_keyboard(lang, CB_MARITAL)


def confirm_keyboard(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [InlineKeyboardButton(t(lang, "btn_start_search"), callback_data=f"{CB_CONFIRM}:start")],
            [InlineKeyboardButton(t(lang, "btn_edit_search"), callback_data=f"{CB_CONFIRM}:edit")],
        ]
    )


# ----- billing ----------------------------------------------------------
_CURRENCY = {
    "UZS": {"uz": "so'm", "ru": "сум", "en": "UZS"},
    "USD": {"uz": "$", "ru": "$", "en": "$"},
}
_DAY = {"uz": "kun", "ru": "дн.", "en": "days"}


def format_price(price, currency: str = "UZS", days=None, lang: str = "uz") -> str:
    """e.g. "12 000 so'm / 3 kun"."""
    if price is None or price == "":
        return ""
    try:
        amount = f"{int(float(price)):,}".replace(",", " ")
    except (TypeError, ValueError):
        return str(price)
    cur = _CURRENCY.get(currency, {}).get(lang, currency)
    text = f"{amount} {cur}".strip()
    if days:
        text += f" / {days} {_DAY.get(lang, 'kun')}"
    return text


def plans_keyboard(lang: str, plans: list[dict]) -> InlineKeyboardMarkup:
    rows = []
    for p in plans:
        label = f"💎 {p.get('name', '—')}"
        price = format_price(p.get("price"), p.get("currency", "UZS"), p.get("period_days"), lang)
        if price:
            label += f" — {price}"
        rows.append([InlineKeyboardButton(label, callback_data=f"{CB_PLAN}:{p['id']}")])
    rows.append([InlineKeyboardButton(t(lang, "btn_menu"), callback_data=f"{CB_NAV}:menu")])
    return InlineKeyboardMarkup(rows)


PROVIDERS: tuple[tuple[str, str], ...] = (
    ("payme", "💙 Payme"),
    ("click", "💚 Click"),
    ("uzum", "💜 Uzum"),
    ("balance", "👛 Balans"),
)


def providers_keyboard(lang: str, plan_id: int) -> InlineKeyboardMarkup:
    rows = [
        [InlineKeyboardButton(label, callback_data=f"{CB_PAY}:{plan_id}:{slug}")]
        for slug, label in PROVIDERS
    ]
    rows.append([InlineKeyboardButton(t(lang, "btn_back"), callback_data=f"{CB_MENU}:pay")])
    return InlineKeyboardMarkup(rows)


def pay_link_keyboard(lang: str, url: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(
        [
            [InlineKeyboardButton(t(lang, "pay_now"), url=url)],
            [InlineKeyboardButton(t(lang, "btn_menu"), callback_data=f"{CB_NAV}:menu")],
        ]
    )
