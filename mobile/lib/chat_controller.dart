import 'dart:async';

import 'package:flutter/foundation.dart';

import 'api.dart';
import 'models.dart';

/// One entry in the chat transcript.
abstract class ChatItem {}

class AiBubble extends ChatItem {
  final String text;
  AiBubble(this.text);
}

class UserBubble extends ChatItem {
  final String text;
  UserBubble(this.text);
}

class CardBubble extends ChatItem {
  final ChatCard card;
  bool locked;
  CardBubble(this.card, {this.locked = false});
}

class SearchingBubble extends ChatItem {
  String stage; // searching | checking | contacting | waiting | done
  String? progressLine;
  SearchingBubble({this.stage = 'searching', this.progressLine});
}

class ResultsBubble extends ChatItem {
  final List<ChatResult> results;
  final String? summary;
  ResultsBubble(this.results, this.summary);
}

/// Drives the whole conversation: holds criteria, talks to [ChatApi], and
/// exposes a flat list of [ChatItem]s for the UI to render.
class ChatController extends ChangeNotifier {
  ChatController({ChatApi? api}) : _api = api ?? ChatApi();

  final ChatApi _api;

  final List<ChatItem> items = [];
  Map<String, dynamic> criteria = {};
  String lang = 'uz';
  bool busy = false;
  Timer? _poll;

  Future<void> init() async {
    items.clear();
    criteria = {};
    _stopPolling();
    notifyListeners();
    await _loadCard(initial: true);
  }

  Future<void> reset() => init();

  Future<void> setLang(String l) async {
    if (l == lang) return;
    lang = l;
    notifyListeners();
    // Re-render the currently open (unlocked) card in the new language.
    if (items.isNotEmpty && items.last is CardBubble && !(items.last as CardBubble).locked) {
      items.removeLast(); // the card
      if (items.isNotEmpty && items.last is AiBubble) items.removeLast(); // its prompt
      notifyListeners();
      await _loadCard();
    }
  }

  Future<void> _loadCard({bool initial = false}) async {
    busy = true;
    notifyListeners();
    try {
      final res = await _api.card(criteria: criteria, lang: lang);
      criteria = res.criteria.isNotEmpty ? res.criteria : criteria;
      items.add(AiBubble(res.reply));
      if (res.card != null) items.add(CardBubble(res.card!));
    } catch (e) {
      debugPrint('[chat] loadCard error: $e');
      if (initial) items.add(AiBubble(_err));
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  /// Submit a structured card (region/budget/rooms/household/musthaves).
  Future<void> submitCard({
    required CardBubble bubble,
    required String field,
    required Object? value,
    required String sentence,
  }) async {
    if (bubble.locked) return;
    bubble.locked = true;
    items.add(UserBubble(sentence));
    notifyListeners();
    await _send(message: sentence, field: field, value: value);
  }

  /// Free-text message (typed or transcribed voice).
  Future<void> sendText(String text) async {
    final t = text.trim();
    if (t.isEmpty || busy) return;
    // Lock any still-open card so the user can't double-answer it.
    for (final it in items) {
      if (it is CardBubble) it.locked = true;
    }
    items.add(UserBubble(t));
    notifyListeners();
    await _send(message: t);
  }

  Future<void> _send({required String message, String? field, Object? value}) async {
    busy = true;
    notifyListeners();
    try {
      final res = await _api.send(
        message: message,
        lang: lang,
        criteria: criteria,
        field: field,
        value: value,
      );
      criteria = res.criteria;
      items.add(AiBubble(res.reply));
      if (res.status == 'searching' && res.searchId != null) {
        final s = SearchingBubble();
        items.add(s);
        criteria = {}; // fresh flow after a search starts
        _startPolling(res.searchId!, s);
      } else if (res.card != null) {
        items.add(CardBubble(res.card!));
      }
    } catch (e) {
      debugPrint('[chat] send error: $e');
      items.add(AiBubble(_err));
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  void _startPolling(int searchId, SearchingBubble bubble) {
    _stopPolling();
    final deadline = DateTime.now().add(const Duration(minutes: 3));
    _poll = Timer.periodic(const Duration(seconds: 3), (timer) async {
      try {
        final st = await _api.status(searchId, lang);
        bubble.stage = st.stage;
        bubble.progressLine = st.contacted > 0
            ? '📨 ${st.contacted} · ✅ ${st.agreed}'
            : null;
        notifyListeners();
        if (st.done) {
          _stopPolling();
          items.remove(bubble);
          items.add(ResultsBubble(st.results, st.summary));
          notifyListeners();
        } else if (DateTime.now().isAfter(deadline)) {
          _stopPolling();
          notifyListeners();
        }
      } catch (_) {/* transient — keep polling */}
    });
  }

  void _stopPolling() {
    _poll?.cancel();
    _poll = null;
  }

  String get _err =>
      {'ru': 'Произошла ошибка. Попробуйте ещё раз.', 'en': 'Something went wrong. Please try again.'}[lang] ??
      'Xatolik yuz berdi. Qayta urinib ko‘ring.';

  @override
  void dispose() {
    _stopPolling();
    super.dispose();
  }
}
