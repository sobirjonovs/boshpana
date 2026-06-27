/// Localized UI strings and the composed user sentences for card submissions
/// (mirrors the website's TPL dict so the app reads identically in uz/ru/en).
class I18n {
  final String lang;
  const I18n(this.lang);

  String _pick(Map<String, String> m) => m[lang] ?? m['uz']!;

  // ---- static UI ----
  String get greeting => _pick({
        'uz': 'Salom 👋 Qanday kvartira topib beray?',
        'ru': 'Привет 👋 Какую квартиру вам найти?',
        'en': 'Hi 👋 What home can I find for you?',
      });

  String get inputHint => _pick({
        'uz': 'Masalan: Toshkentdan 300\$ gacha, 2 xonali…',
        'ru': 'Напр.: в Ташкенте до 300\$, 2 комнаты…',
        'en': 'e.g. 2-room in Tashkent up to \$300…',
      });

  String get newChat => _pick({'uz': 'Yangi suhbat', 'ru': 'Новый чат', 'en': 'New chat'});
  String get listening =>
      _pick({'uz': 'Tinglayapman…', 'ru': 'Слушаю…', 'en': 'Listening…'});
  String get searching => _pick({
        'uz': 'Uy egalari bilan Telegram orqali bog‘lanmoqdaman…',
        'ru': 'Связываюсь с владельцами в Telegram…',
        'en': 'Contacting owners on Telegram…',
      });

  String stageLabel(String stage) {
    switch (stage) {
      case 'checking':
        return _pick({'uz': 'E’lonlar tekshirilmoqda', 'ru': 'Проверяю объявления', 'en': 'Checking listings'});
      case 'contacting':
        return _pick({'uz': 'Uy egalariga yozilmoqda', 'ru': 'Пишу владельцам', 'en': 'Talking to owners'});
      case 'waiting':
        return _pick({'uz': 'Javoblar kutilmoqda', 'ru': 'Жду ответы', 'en': 'Waiting for replies'});
      case 'done':
        return _pick({'uz': 'Tayyor', 'ru': 'Готово', 'en': 'Done'});
      default:
        return _pick({'uz': 'Qidirilmoqda', 'ru': 'Идёт поиск', 'en': 'Searching'});
    }
  }
  String get noResults => _pick({
        'uz': 'Afsus, bu safar hech bir uy egasi rozi bo‘lmadi. Boshqa shartlar bilan urinib ko‘ring.',
        'ru': 'К сожалению, никто не согласился. Попробуйте другие условия.',
        'en': 'Sorry, no owner agreed this time. Try different criteria.',
      });
  String get view => _pick({'uz': 'Ko‘rish', 'ru': 'Открыть', 'en': 'View'});
  String get match => _pick({'uz': 'moslik', 'ru': 'совпадение', 'en': 'match'});
  String get rooms => _pick({'uz': 'xona', 'ru': 'комн.', 'en': 'rooms'});
  String get micDenied => _pick({
        'uz': 'Mikrofonga ruxsat berilmadi.',
        'ru': 'Нет доступа к микрофону.',
        'en': 'Microphone permission denied.',
      });
  String get errorMsg => _pick({
        'uz': 'Xatolik yuz berdi. Qayta urinib ko‘ring.',
        'ru': 'Произошла ошибка. Попробуйте ещё раз.',
        'en': 'Something went wrong. Please try again.',
      });

  // ---- composed user sentences for card submissions ----
  String region(String label) => _pick({
        'uz': '$label' 'dan qidiryapman.',
        'ru': 'Ищу в $label.',
        'en': "I'm looking in $label.",
      });

  String budget(String label) => _pick({
        'uz': 'Byudjetim $label.',
        'ru': 'Мой бюджет $label.',
        'en': 'My budget is $label.',
      });

  String household(int occupants, bool furnished) {
    var base = _pick({
      'uz': 'Uyda $occupants kishi yashaydi.',
      'ru': 'В квартире будет проживать $occupants чел.',
      'en': 'My household has $occupants occupant(s).',
    });
    if (furnished) {
      base += _pick({
        'uz': ' Mebelli bo‘lsa yaxshi.',
        'ru': ' Желательно с мебелью.',
        'en': " I'd prefer it furnished.",
      });
    }
    return base;
  }

  String roomsSentence(String value, String label) {
    if (value == 'any') {
      return _pick({
        'uz': 'Xonalar soni farqi yo‘q.',
        'ru': 'Количество комнат не важно.',
        'en': "Number of rooms doesn't matter.",
      });
    }
    return _pick({
      'uz': 'Menga $value xonali kerak.',
      'ru': 'Мне нужно $value комн.',
      'en': 'I need $value room(s).',
    });
  }

  String mustHaves(List<String> labels) {
    if (labels.isEmpty) {
      return _pick({
        'uz': 'Maxsus talab yo‘q.',
        'ru': 'Без особых условий.',
        'en': 'No specific must-haves.',
      });
    }
    final list = labels.join(', ');
    return _pick({
      'uz': 'Talablarim: $list.',
      'ru': 'Обязательно: $list.',
      'en': 'Must-haves: $list.',
    });
  }
}

const supportedLangs = ['uz', 'ru', 'en'];
String langName(String l) =>
    {'uz': 'UZ', 'ru': 'RU', 'en': 'EN'}[l] ?? l.toUpperCase();
