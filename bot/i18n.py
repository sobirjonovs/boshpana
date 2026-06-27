"""Translations for the Boshpana.ai bot (uz primary, ru, en).

Use t(lang, key, **kw) to fetch a localised string. Missing keys fall back to
Uzbek, then to the key itself, so the bot never crashes on a typo.
"""

from __future__ import annotations

from typing import Any

import config

LANG_NAMES: dict[str, str] = {
    "uz": "🇺🇿 O'zbekcha",
    "ru": "🇷🇺 Русский",
    "en": "🇬🇧 English",
}

STRINGS: dict[str, dict[str, str]] = {
    "uz": {
        # ----- onboarding / menu -----
        "choose_language": "🌐 Tilni tanlang:",
        "language_set": "✅ Til o'zgartirildi.",
        "greeting": (
            "🏠 <b>Boshpana.ai</b> ga xush kelibsiz!\n\n"
            "Men Toshkentdan sizga mos kvartirani topaman: ko'plab e'lonlarni "
            "skanerlab, uy egalari bilan gaplashib, faqat roziligi bor variantlarni "
            "ko'rsataman.\n\nQuyidagi menyudan boshlang 👇"
        ),
        "main_menu": "🏠 Asosiy menyu:",
        "btn_search": "🔎 Qidiruv",
        "btn_payment": "💳 To'lov",
        "btn_settings": "⚙️ Sozlamalar",
        "btn_back": "⬅️ Orqaga",
        "btn_skip": "⏭ O'tkazib yuborish",
        "btn_continue": "➡️ Davom etish",
        "btn_menu": "🏠 Menyu",
        # ----- settings -----
        "settings_title": "⚙️ Sozlamalar:",
        "btn_change_language": "🌐 Tilni o'zgartirish",
        "btn_set_gender": "🧍 Jins",
        "btn_set_marital": "💍 Oilaviy holat",
        "profile_line": "🌐 Til: {lang_name}\n🧍 Jins: {gender}\n💍 Holat: {marital}",
        "choose_gender": "🧍 Jinsingizni tanlang:",
        "choose_marital": "💍 Oilaviy holatingizni tanlang:",
        "saved": "✅ Saqlandi.",
        # ----- payment -----
        "payment_title": "💳 Tariflar:\n\nBalans: <b>{balance} so'm</b>",
        "choose_plan": "💳 Tarifni tanlang:",
        "choose_provider": "💳 To'lov usulini tanlang:\n\n<b>{plan}</b> — {price}",
        "no_plans": "Hozircha tariflar mavjud emas.",
        "payment_created": (
            "💳 To'lov yaratildi.\n\nTarif: <b>{plan}</b>\nSumma: <b>{amount}</b>\n"
            "Holat: {status}"
        ),
        "pay_now": "👉 To'lash",
        "no_pay_url": "To'lov havolasi keyinroq beriladi. Holat: {status}",
        # ----- search flow -----
        "step_region": "1️⃣ Viloyatni tanlang:",
        "step_district": "2️⃣ Tuman/shaharni tanlang:",
        "step_price": (
            "3️⃣ Narxni kiriting (USD).\n\nMasalan: <code>300</code> yoki "
            "<code>300-400</code>."
        ),
        "price_invalid": "❌ Noto'g'ri narx. Masalan: 300 yoki 300-400.",
        "step_rooms": "4️⃣ Xonalar sonini tanlang (bir nechtasini belgilash mumkin):",
        "rooms_hint": "Belgilangan: {rooms}",
        "rooms_none": "yo'q",
        "step_condition": "5️⃣ Holati:",
        "step_furniture": "6️⃣ Mebel (mebel, konditsioner, kir mashina...) bo'lsinmi?",
        "step_commission": "7️⃣ Maklerlik (komissiya) bo'lsinmi?",
        "step_area": (
            "8️⃣ Maydon (m²) — ixtiyoriy.\n\nMasalan: <code>40</code> yoki "
            "<code>40-60</code>. Yoki o'tkazib yuboring."
        ),
        "area_invalid": "❌ Noto'g'ri maydon. Masalan: 40 yoki 40-60.",
        "step_mode": "9️⃣ Qidiruv turi:",
        "step_partners": "👥 Nechta sherik kerak?",
        "step_metro": "🚇 Metroga yaqin bo'lsinmi?",
        "step_gender": "1️⃣1️⃣ Jins talabi:",
        "step_marital": "1️⃣2️⃣ Oilaviy holat talabi:",
        "step_confirm": "✅ Qidiruv shartlari:\n\n{summary}\n\nQidiruvni boshlaymizmi?",
        "btn_start_search": "✅ Qidiruvni boshlash",
        "btn_edit_search": "✏️ O'zgartirish",
        # ----- options -----
        "opt_any": "🤷 Farqi yo'q",
        "opt_yes": "✅ Ha",
        "opt_no": "❌ Yo'q",
        "cond_average": "🙂 O'rtacha",
        "cond_excellent": "🤩 Zo'r",
        "mode_solo": "🧍 Faqat o'zim",
        "mode_partnership": "👥 Sherikchilik",
        "partners_self": "🧍 O'zim",
        "gender_male": "👨 Erkak",
        "gender_female": "👩 Ayol",
        "marital_single": "💚 Bo'ydoq",
        "marital_married": "💍 Uylangan",
        "room_label": "🚪 {n}",
        # ----- progress / results -----
        "cannot_search": (
            "⚠️ Bepul qidiruvlaringiz tugagan. Davom etish uchun 💳 To'lov bo'limidan "
            "tarif sotib oling."
        ),
        "search_starting": "🚀 Qidiruv boshlandi...",
        "progress": (
            "{spinner} <b>Qidiruv ketmoqda...</b>\n\n"
            "📊 Bajarildi: <b>{progress}%</b>\n"
            "🔍 Skaner qilindi: <b>{scanned}</b>\n"
            "📞 Bog'lanildi: <b>{contacted}</b>\n"
            "🤝 Rozi bo'ldi: <b>{agreed}</b>\n\n"
            "🕒 Yangilandi: {time}"
        ),
        "found_notify": (
            "✅ <b>1 ta topildi!</b>\n\n📍 {address}\n💵 {price}$\n🔗 {link}"
        ),
        "search_done": (
            "🎉 <b>Qidiruv yakunlandi!</b>\n\n"
            "🤝 Rozi bo'lgan: <b>{agreed}</b> · 🔍 Skaner: <b>{scanned}</b>"
        ),
        "no_results": (
            "😔 Afsuski, shartlaringizga mos, egasi rozi bo'lgan variant topilmadi.\n"
            "Shartlarni yumshatib qayta urinib ko'ring."
        ),
        "result_card": (
            "🏠 <b>{title}</b>\n💵 {price}$ · 🚪 {rooms} xona{area}\n"
            "📍 {district} · {condition}\n🔗 {source}: {link}"
        ),
        "search_cancelled": "🛑 Qidiruv bekor qilindi.",
        # ----- misc -----
        "error": "⚠️ Xatolik yuz berdi. Birozdan so'ng qayta urinib ko'ring.",
        "session_expired": "⌛️ Sessiya tugadi. /start ni bosing.",
        "none": "—",
    },
    "ru": {
        "choose_language": "🌐 Выберите язык:",
        "language_set": "✅ Язык изменён.",
        "greeting": (
            "🏠 Добро пожаловать в <b>Boshpana.ai</b>!\n\n"
            "Я найду квартиру в Ташкенте под вас: сканирую множество объявлений, "
            "общаюсь с владельцами и показываю только тех, кто согласен на условия.\n\n"
            "Начните из меню ниже 👇"
        ),
        "main_menu": "🏠 Главное меню:",
        "btn_search": "🔎 Поиск",
        "btn_payment": "💳 Оплата",
        "btn_settings": "⚙️ Настройки",
        "btn_back": "⬅️ Назад",
        "btn_skip": "⏭ Пропустить",
        "btn_continue": "➡️ Продолжить",
        "btn_menu": "🏠 Меню",
        "settings_title": "⚙️ Настройки:",
        "btn_change_language": "🌐 Сменить язык",
        "btn_set_gender": "🧍 Пол",
        "btn_set_marital": "💍 Семейное положение",
        "profile_line": "🌐 Язык: {lang_name}\n🧍 Пол: {gender}\n💍 Статус: {marital}",
        "choose_gender": "🧍 Выберите пол:",
        "choose_marital": "💍 Выберите семейное положение:",
        "saved": "✅ Сохранено.",
        "payment_title": "💳 Тарифы:\n\nБаланс: <b>{balance} сум</b>",
        "choose_plan": "💳 Выберите тариф:",
        "choose_provider": "💳 Выберите способ оплаты:\n\n<b>{plan}</b> — {price}",
        "no_plans": "Тарифов пока нет.",
        "payment_created": (
            "💳 Платёж создан.\n\nТариф: <b>{plan}</b>\nСумма: <b>{amount}</b>\n"
            "Статус: {status}"
        ),
        "pay_now": "👉 Оплатить",
        "no_pay_url": "Ссылка на оплату будет позже. Статус: {status}",
        "step_region": "1️⃣ Выберите регион:",
        "step_district": "2️⃣ Выберите район/город:",
        "step_price": (
            "3️⃣ Введите цену (USD).\n\nНапример: <code>300</code> или "
            "<code>300-400</code>."
        ),
        "price_invalid": "❌ Неверная цена. Например: 300 или 300-400.",
        "step_rooms": "4️⃣ Выберите количество комнат (можно несколько):",
        "rooms_hint": "Выбрано: {rooms}",
        "rooms_none": "нет",
        "step_condition": "5️⃣ Состояние:",
        "step_furniture": "6️⃣ Нужна мебель (мебель, кондиционер, стиралка...)?",
        "step_commission": "7️⃣ Допустима комиссия (риелтор)?",
        "step_area": (
            "8️⃣ Площадь (м²) — необязательно.\n\nНапример: <code>40</code> или "
            "<code>40-60</code>. Либо пропустите."
        ),
        "area_invalid": "❌ Неверная площадь. Например: 40 или 40-60.",
        "step_mode": "9️⃣ Тип поиска:",
        "step_partners": "👥 Сколько соседей нужно?",
        "step_metro": "🚇 Рядом с метро?",
        "step_gender": "1️⃣1️⃣ Требование к полу:",
        "step_marital": "1️⃣2️⃣ Требование к семейному положению:",
        "step_confirm": "✅ Условия поиска:\n\n{summary}\n\nНачинаем поиск?",
        "btn_start_search": "✅ Начать поиск",
        "btn_edit_search": "✏️ Изменить",
        "opt_any": "🤷 Неважно",
        "opt_yes": "✅ Да",
        "opt_no": "❌ Нет",
        "cond_average": "🙂 Среднее",
        "cond_excellent": "🤩 Отличное",
        "mode_solo": "🧍 Только я",
        "mode_partnership": "👥 С соседями",
        "partners_self": "🧍 Сам",
        "gender_male": "👨 Мужчина",
        "gender_female": "👩 Женщина",
        "marital_single": "💚 Холост",
        "marital_married": "💍 Женат",
        "room_label": "🚪 {n}",
        "cannot_search": (
            "⚠️ Бесплатные поиски закончились. Купите тариф в разделе 💳 Оплата."
        ),
        "search_starting": "🚀 Поиск запущен...",
        "progress": (
            "{spinner} <b>Идёт поиск...</b>\n\n"
            "📊 Выполнено: <b>{progress}%</b>\n"
            "🔍 Просканировано: <b>{scanned}</b>\n"
            "📞 Связались: <b>{contacted}</b>\n"
            "🤝 Согласились: <b>{agreed}</b>\n\n"
            "🕒 Обновлено: {time}"
        ),
        "found_notify": (
            "✅ <b>Найдено 1!</b>\n\n📍 {address}\n💵 {price}$\n🔗 {link}"
        ),
        "search_done": (
            "🎉 <b>Поиск завершён!</b>\n\n"
            "🤝 Согласились: <b>{agreed}</b> · 🔍 Скан: <b>{scanned}</b>"
        ),
        "no_results": (
            "😔 К сожалению, согласных владельцев под ваши условия не нашлось.\n"
            "Попробуйте смягчить условия."
        ),
        "result_card": (
            "🏠 <b>{title}</b>\n💵 {price}$ · 🚪 {rooms} комн.{area}\n"
            "📍 {district} · {condition}\n🔗 {source}: {link}"
        ),
        "search_cancelled": "🛑 Поиск отменён.",
        "error": "⚠️ Произошла ошибка. Попробуйте позже.",
        "session_expired": "⌛️ Сессия истекла. Нажмите /start.",
        "none": "—",
    },
    "en": {
        "choose_language": "🌐 Choose your language:",
        "language_set": "✅ Language updated.",
        "greeting": (
            "🏠 Welcome to <b>Boshpana.ai</b>!\n\n"
            "I find an apartment in Tashkent for you: I scan many listings, talk to "
            "owners, and show only those who agree to your conditions.\n\n"
            "Start from the menu below 👇"
        ),
        "main_menu": "🏠 Main menu:",
        "btn_search": "🔎 Search",
        "btn_payment": "💳 Payment",
        "btn_settings": "⚙️ Settings",
        "btn_back": "⬅️ Back",
        "btn_skip": "⏭ Skip",
        "btn_continue": "➡️ Continue",
        "btn_menu": "🏠 Menu",
        "settings_title": "⚙️ Settings:",
        "btn_change_language": "🌐 Change language",
        "btn_set_gender": "🧍 Gender",
        "btn_set_marital": "💍 Marital status",
        "profile_line": "🌐 Language: {lang_name}\n🧍 Gender: {gender}\n💍 Status: {marital}",
        "choose_gender": "🧍 Choose your gender:",
        "choose_marital": "💍 Choose your marital status:",
        "saved": "✅ Saved.",
        "payment_title": "💳 Plans:\n\nBalance: <b>{balance} UZS</b>",
        "choose_plan": "💳 Choose a plan:",
        "choose_provider": "💳 Choose a payment method:\n\n<b>{plan}</b> — {price}",
        "no_plans": "No plans available yet.",
        "payment_created": (
            "💳 Payment created.\n\nPlan: <b>{plan}</b>\nAmount: <b>{amount}</b>\n"
            "Status: {status}"
        ),
        "pay_now": "👉 Pay now",
        "no_pay_url": "Payment link will be provided later. Status: {status}",
        "step_region": "1️⃣ Choose a region:",
        "step_district": "2️⃣ Choose a district/city:",
        "step_price": (
            "3️⃣ Enter the price (USD).\n\nE.g. <code>300</code> or <code>300-400</code>."
        ),
        "price_invalid": "❌ Invalid price. E.g. 300 or 300-400.",
        "step_rooms": "4️⃣ Choose number of rooms (multi-select):",
        "rooms_hint": "Selected: {rooms}",
        "rooms_none": "none",
        "step_condition": "5️⃣ Condition:",
        "step_furniture": "6️⃣ Furniture (furniture, AC, washer...) needed?",
        "step_commission": "7️⃣ Realtor commission allowed?",
        "step_area": (
            "8️⃣ Area (m²) — optional.\n\nE.g. <code>40</code> or <code>40-60</code>. "
            "Or skip."
        ),
        "area_invalid": "❌ Invalid area. E.g. 40 or 40-60.",
        "step_mode": "9️⃣ Search type:",
        "step_partners": "👥 How many flatmates needed?",
        "step_metro": "🚇 Near metro?",
        "step_gender": "1️⃣1️⃣ Gender requirement:",
        "step_marital": "1️⃣2️⃣ Marital status requirement:",
        "step_confirm": "✅ Search criteria:\n\n{summary}\n\nStart the search?",
        "btn_start_search": "✅ Start search",
        "btn_edit_search": "✏️ Edit",
        "opt_any": "🤷 Any",
        "opt_yes": "✅ Yes",
        "opt_no": "❌ No",
        "cond_average": "🙂 Average",
        "cond_excellent": "🤩 Excellent",
        "mode_solo": "🧍 Just me",
        "mode_partnership": "👥 Partnership",
        "partners_self": "🧍 Myself",
        "gender_male": "👨 Male",
        "gender_female": "👩 Female",
        "marital_single": "💚 Single",
        "marital_married": "💍 Married",
        "room_label": "🚪 {n}",
        "cannot_search": (
            "⚠️ You're out of free searches. Buy a plan in the 💳 Payment section."
        ),
        "search_starting": "🚀 Search started...",
        "progress": (
            "{spinner} <b>Searching...</b>\n\n"
            "📊 Progress: <b>{progress}%</b>\n"
            "🔍 Scanned: <b>{scanned}</b>\n"
            "📞 Contacted: <b>{contacted}</b>\n"
            "🤝 Agreed: <b>{agreed}</b>\n\n"
            "🕒 Updated: {time}"
        ),
        "found_notify": (
            "✅ <b>1 found!</b>\n\n📍 {address}\n💵 {price}$\n🔗 {link}"
        ),
        "search_done": (
            "🎉 <b>Search complete!</b>\n\n"
            "🤝 Agreed: <b>{agreed}</b> · 🔍 Scanned: <b>{scanned}</b>"
        ),
        "no_results": (
            "😔 No owners agreed to your conditions this time.\n"
            "Try relaxing the criteria."
        ),
        "result_card": (
            "🏠 <b>{title}</b>\n💵 {price}$ · 🚪 {rooms} rooms{area}\n"
            "📍 {district} · {condition}\n🔗 {source}: {link}"
        ),
        "search_cancelled": "🛑 Search cancelled.",
        "error": "⚠️ Something went wrong. Try again later.",
        "session_expired": "⌛️ Session expired. Press /start.",
        "none": "—",
    },
}


def t(lang: str | None, key: str, **kw: Any) -> str:
    """Translate `key` for `lang`, falling back to uz then the key itself."""
    lang = (lang or config.DEFAULT_LANG).lower()
    if lang not in STRINGS:
        lang = "uz"
    template = STRINGS[lang].get(key) or STRINGS["uz"].get(key) or key
    if kw:
        try:
            return template.format(**kw)
        except (KeyError, IndexError):
            return template
    return template
