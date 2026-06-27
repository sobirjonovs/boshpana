# Boshpana.ai — Mobile (Flutter)

The Boshpana.ai apartment-finder as a native Android/iOS app — a 1:1 version of
the website chat: green theme, guided interactive cards (region → budget →
who's-moving-in stepper/toggle → rooms → must-haves), free-text + **voice**
input, live "contacting owners" search with result cards, and uz/ru/en.

It talks to the **same backend** as the website via a stateless API
(`/api/v1/chat/card`, `/chat/send`, `/chat/status/{id}`) — the app holds the
criteria and passes it on every call (no cookies/CSRF).

## Architecture
```
lib/
  main.dart            app entry + MaterialApp (green theme)
  theme.dart           brand palette (#13a06a) + ThemeData
  i18n.dart            uz/ru/en UI strings + composed user sentences
  models.dart          ChatCard / Choice / CardField / ChatResult / responses
  api.dart             ChatApi — http client to the stateless chat API
  chat_controller.dart conversation state (ChangeNotifier) + polling
  card_view.dart       interactive cards (chips, stepper, toggle, multi-select)
  chat_screen.dart     app bar, bubbles, results, composer + mic (speech_to_text)
```

## Run

1. Start the backend (from `../backend`):
   ```bash
   php artisan serve --host=0.0.0.0 --port=8088   # 0.0.0.0 so devices/emulators can reach it
   ```
   (Keep the queue worker + userbot running too, as for the website.)

2. Point the app at the backend. The default in `lib/api.dart` auto-targets:
   - **Android emulator** -> `http://10.0.2.2:8088/api/v1` (host loopback)
   - **iOS simulator** -> `http://127.0.0.1:8088/api/v1`
   - **Real phone** -> set your computer's LAN IP, e.g. construct
     `ChatController(api: ChatApi(baseUrl: 'http://192.168.1.20:8088/api/v1'))`
     in `chat_screen.dart`.

3. Run:
   ```bash
   flutter pub get
   flutter run            # pick a device/emulator
   ```

## Notes
- Voice input uses the on-device recognizer (`speech_to_text`); permissions are
  declared (Android `RECORD_AUDIO`, iOS mic/speech usage strings). Uzbek coverage
  depends on the device's recognizer; it degrades gracefully.
- Cleartext HTTP is enabled for local dev (Android `usesCleartextTraffic`, iOS
  `NSAllowsLocalNetworking`). Use HTTPS in production.
- Photo/location attach (the "+" menu) is present as a stub; full image/map
  support needs native plugins (image_picker / geolocator / flutter_map) as a
  follow-up. Location can already be given as free text in chat (e.g. "Chilonzor").
