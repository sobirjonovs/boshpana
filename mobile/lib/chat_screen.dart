import 'dart:async';

import 'package:flutter/material.dart';
import 'package:speech_to_text/speech_to_text.dart';
import 'package:url_launcher/url_launcher.dart';

import 'card_view.dart';
import 'chat_controller.dart';
import 'i18n.dart';
import 'models.dart';
import 'theme.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final ChatController _c = ChatController();
  final ScrollController _scroll = ScrollController();
  final TextEditingController _input = TextEditingController();
  final SpeechToText _speech = SpeechToText();
  bool _listening = false;
  int _lastCount = 0;
  bool _lastBusy = false;

  I18n get _i => I18n(_c.lang);

  @override
  void initState() {
    super.initState();
    _c.addListener(_onChange);
    _c.init();
  }

  void _onChange() {
    setState(() {});
    if (_c.items.length != _lastCount || (_c.busy && !_lastBusy)) {
      _lastCount = _c.items.length;
      WidgetsBinding.instance.addPostFrameCallback((_) => _toBottom());
    }
    _lastBusy = _c.busy;
  }

  void _toBottom() {
    if (!_scroll.hasClients) return;
    _scroll.animateTo(_scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
  }

  @override
  void dispose() {
    _c.removeListener(_onChange);
    _c.dispose();
    _scroll.dispose();
    _input.dispose();
    super.dispose();
  }

  // ---------------------------------------------------------------- voice
  Future<void> _toggleMic() async {
    if (_listening) {
      await _speech.stop();
      setState(() => _listening = false);
      return;
    }
    final ok = await _speech.initialize(onStatus: (s) {
      if (s == 'done' || s == 'notListening') {
        if (mounted) setState(() => _listening = false);
      }
    });
    if (!ok) {
      _toast(_i.micDenied);
      return;
    }
    setState(() => _listening = true);
    await _speech.listen(
      localeId: {'uz': 'uz_UZ', 'ru': 'ru_RU', 'en': 'en_US'}[_c.lang],
      onResult: (r) => setState(() => _input.text = r.recognizedWords),
    );
  }

  void _toast(String m) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(m)));

  void _submitInput() {
    final t = _input.text.trim();
    if (t.isEmpty) return;
    _input.clear();
    if (_listening) {
      _speech.stop();
      _listening = false;
    }
    _c.sendText(t);
  }

  // ---------------------------------------------------------------- build
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.page,
      appBar: _appBar(),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Expanded(
              child: ListView.builder(
                controller: _scroll,
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 18),
                itemCount: _c.items.length + 1 + (_c.busy ? 1 : 0),
                itemBuilder: (ctx, i) {
                  if (i == 0) return _greeting();
                  final idx = i - 1;
                  if (idx < _c.items.length) return _item(_c.items[idx]);
                  return const _TypingIndicator(); // shown while waiting for the AI
                },
              ),
            ),
            _composer(),
          ],
        ),
      ),
    );
  }

  PreferredSizeWidget _appBar() {
    return AppBar(
      backgroundColor: AppColors.page,
      elevation: 0,
      scrolledUnderElevation: 0.5,
      titleSpacing: 16,
      title: Row(children: [
        Container(
            width: 13,
            height: 13,
            decoration: const BoxDecoration(color: AppColors.green, shape: BoxShape.circle)),
        const SizedBox(width: 8),
        const Text('boshpana.ai',
            style: TextStyle(
                color: AppColors.slate, fontWeight: FontWeight.w700, fontSize: 18.5)),
      ]),
      actions: [
        PopupMenuButton<String>(
          tooltip: 'Language',
          onSelected: _c.setLang,
          itemBuilder: (_) => supportedLangs
              .map((l) => PopupMenuItem(value: l, child: Text(langName(l))))
              .toList(),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
            child: Row(children: [
              Text(langName(_c.lang),
                  style: const TextStyle(color: AppColors.slate, fontWeight: FontWeight.w600)),
              const Icon(Icons.arrow_drop_down, color: AppColors.slate),
            ]),
          ),
        ),
        IconButton(
          tooltip: _i.newChat,
          onPressed: () => _c.reset(),
          icon: const Icon(Icons.edit_square, color: AppColors.slate, size: 22),
        ),
        const SizedBox(width: 4),
      ],
    );
  }

  Widget _greeting() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 10, 4, 18),
      child: Text(_i.greeting,
          style: const TextStyle(
              fontSize: 26, fontWeight: FontWeight.w700, color: AppColors.green, height: 1.2)),
    );
  }

  Widget _item(ChatItem it) {
    if (it is AiBubble) return _ai(it.text);
    if (it is UserBubble) return _user(it.text);
    if (it is CardBubble) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: CardView(
          card: it.card,
          i18n: _i,
          locked: it.locked,
          onSubmit: (field, value, sentence) =>
              _c.submitCard(bubble: it, field: field, value: value, sentence: sentence),
        ),
      );
    }
    if (it is SearchingBubble) return _searching(it);
    if (it is ResultsBubble) return _results(it);
    return const SizedBox.shrink();
  }

  Widget _ai(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          width: 28,
          height: 28,
          decoration: const BoxDecoration(
              gradient: LinearGradient(colors: [AppColors.green, AppColors.greenDark]),
              shape: BoxShape.circle),
          child: const Icon(Icons.auto_awesome, size: 15, color: Colors.white),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border)),
            child: Text(text, style: const TextStyle(fontSize: 15, height: 1.4, color: AppColors.slate)),
          ),
        ),
      ]),
    );
  }

  Widget _user(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Align(
        alignment: Alignment.centerRight,
        child: Container(
          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
          decoration: BoxDecoration(
              color: AppColors.userBubble, borderRadius: BorderRadius.circular(16)),
          child: Text(text, style: const TextStyle(fontSize: 15, height: 1.4, color: AppColors.slate)),
        ),
      ),
    );
  }

  Widget _searching(SearchingBubble b) => _SearchingView(bubble: b, i18n: _i);

  Widget _results(ResultsBubble b) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (b.results.isEmpty) _ai(_i.noResults) else ...b.results.map(_resultCard),
        if (b.summary != null) ...[
          const SizedBox(height: 4),
          _ai(b.summary!),
        ],
      ]),
    );
  }

  Widget _resultCard(ChatResult r) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border)),
      clipBehavior: Clip.antiAlias,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (r.images.isNotEmpty)
          Image.network(r.images.first,
              height: 150,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => _imgPlaceholder())
        else
          _imgPlaceholder(),
        Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(
                child: Text(r.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 15.5, fontWeight: FontWeight.w600, color: AppColors.slate)),
              ),
              if (r.price != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                      color: AppColors.greenSoft, borderRadius: BorderRadius.circular(999)),
                  child: Text('\$${r.price}',
                      style: const TextStyle(
                          color: AppColors.greenDark, fontWeight: FontWeight.w700, fontSize: 14)),
                ),
            ]),
            const SizedBox(height: 8),
            Wrap(spacing: 12, runSpacing: 4, children: [
              if (r.rooms != null) _meta('🚪 ${r.rooms} ${_i.rooms}'),
              if (r.area != null) _meta('📐 ${r.area} m²'),
              if (r.district != null) _meta('📍 ${r.district}'),
              if (r.nearMetro == 'yes') _meta('🚇'),
            ]),
            const SizedBox(height: 12),
            Row(children: [
              if (r.source != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                      color: AppColors.greenSoft2, borderRadius: BorderRadius.circular(6)),
                  child: Text(r.source!,
                      style: const TextStyle(fontSize: 12, color: AppColors.textSoft)),
                ),
              const Spacer(),
              if (r.url != null)
                TextButton(
                  onPressed: () => _open(r.url!),
                  style: TextButton.styleFrom(foregroundColor: AppColors.green),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    Text(_i.view, style: const TextStyle(fontWeight: FontWeight.w600)),
                    const Icon(Icons.open_in_new, size: 16),
                  ]),
                ),
            ]),
          ]),
        ),
      ]),
    );
  }

  Widget _meta(String t) =>
      Text(t, style: const TextStyle(fontSize: 13, color: AppColors.textSoft));

  Widget _imgPlaceholder() => Container(
        height: 110,
        width: double.infinity,
        decoration: const BoxDecoration(
            gradient: LinearGradient(colors: [AppColors.greenSoft, AppColors.greenSoft2])),
        child: const Icon(Icons.home_work_outlined, color: AppColors.green, size: 40),
      );

  Future<void> _open(String url) async {
    final uri = Uri.tryParse(url);
    if (uri != null) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Widget _composer() {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      decoration: const BoxDecoration(
          color: AppColors.page,
          border: Border(top: BorderSide(color: AppColors.border))),
      // One full-width pill with all controls INSIDE it.
      child: Container(
        decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(26),
            border: Border.all(color: _listening ? AppColors.green : AppColors.border)),
        padding: const EdgeInsets.fromLTRB(4, 2, 5, 2),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            IconButton(
              onPressed: _attachSheet,
              visualDensity: VisualDensity.compact,
              icon: const Icon(Icons.add, color: AppColors.textSoft, size: 24),
            ),
            Expanded(
              child: TextField(
                controller: _input,
                minLines: 1,
                maxLines: 5,
                style: const TextStyle(fontSize: 16, color: AppColors.slate),
                textInputAction: TextInputAction.send,
                onSubmitted: (_) => _submitInput(),
                decoration: InputDecoration(
                  isCollapsed: true,
                  contentPadding: const EdgeInsets.symmetric(vertical: 11),
                  border: InputBorder.none,
                  hintText: _listening ? _i.listening : _i.inputHint,
                  hintStyle: const TextStyle(color: AppColors.textFaint, fontSize: 15),
                ),
              ),
            ),
            IconButton(
              onPressed: _toggleMic,
              visualDensity: VisualDensity.compact,
              icon: Icon(_listening ? Icons.stop_circle : Icons.mic_none,
                  color: _listening ? AppColors.green : AppColors.textSoft, size: 23),
            ),
            const SizedBox(width: 2),
            Material(
              color: AppColors.green,
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: _submitInput,
                child: const Padding(
                  padding: EdgeInsets.all(9),
                  child: Icon(Icons.arrow_upward, color: Colors.white, size: 20),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _attachSheet() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(
            leading: const Icon(Icons.place_outlined, color: AppColors.green),
            title: Text({'ru': 'Локация', 'en': 'Location'}[_c.lang] ?? 'Lokatsiya'),
            subtitle: Text({
              'ru': 'Напишите район/город в чате',
              'en': 'Type a district / city in chat',
            }[_c.lang] ?? 'Tuman/shaharni chatda yozing'),
            onTap: () => Navigator.pop(context),
          ),
          ListTile(
            leading: const Icon(Icons.image_outlined, color: AppColors.green),
            title: Text({'ru': 'Фото', 'en': 'Photo'}[_c.lang] ?? 'Rasm'),
            subtitle: Text({'ru': 'Скоро', 'en': 'Coming soon'}[_c.lang] ?? 'Tez orada'),
            onTap: () => Navigator.pop(context),
          ),
        ]),
      ),
    );
  }
}

/// Animated "AI is typing" bubble (green sparkle avatar + 3 bouncing dots).
class _TypingIndicator extends StatefulWidget {
  const _TypingIndicator();

  @override
  State<_TypingIndicator> createState() => _TypingIndicatorState();
}

class _TypingIndicatorState extends State<_TypingIndicator>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl =
      AnimationController(vsync: this, duration: const Duration(milliseconds: 1100))
        ..repeat();

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          width: 28,
          height: 28,
          decoration: const BoxDecoration(
              gradient: LinearGradient(colors: [AppColors.green, AppColors.greenDark]),
              shape: BoxShape.circle),
          child: const Icon(Icons.auto_awesome, size: 15, color: Colors.white),
        ),
        const SizedBox(width: 10),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border)),
          child: AnimatedBuilder(
            animation: _ctrl,
            builder: (_, __) => Row(mainAxisSize: MainAxisSize.min, children: [
              _dot(0),
              const SizedBox(width: 5),
              _dot(1),
              const SizedBox(width: 5),
              _dot(2),
            ]),
          ),
        ),
      ]),
    );
  }

  Widget _dot(int i) {
    final t = (_ctrl.value + i * 0.2) % 1.0;
    final scale = 0.6 + 0.4 * (t < 0.5 ? t * 2 : (1 - t) * 2);
    return Container(
      width: 7 * scale + 3,
      height: 7 * scale + 3,
      decoration: BoxDecoration(
          color: AppColors.green.withValues(alpha: 0.4 + 0.6 * scale),
          shape: BoxShape.circle),
    );
  }
}

/// Live multi-stage search progress (Searching → Checking → Talking to owners →
/// Waiting replies → Done). The early stages auto-advance on a timer so the user
/// always sees the full process; later stages follow the backend `stage`.
class _SearchingView extends StatefulWidget {
  const _SearchingView({required this.bubble, required this.i18n});
  final SearchingBubble bubble;
  final I18n i18n;

  @override
  State<_SearchingView> createState() => _SearchingViewState();
}

class _SearchingViewState extends State<_SearchingView> {
  int _minIndex = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(milliseconds: 1100), (t) {
      if (_minIndex >= 2) {
        t.cancel();
        return;
      }
      if (mounted) setState(() => _minIndex++);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final backendIndex = searchStages.indexOf(widget.bubble.stage).clamp(0, 4);
    final current = _minIndex > backendIndex ? _minIndex : backendIndex;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        decoration: BoxDecoration(
            color: AppColors.greenSoft2,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.greenSoft)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (var i = 0; i < searchStages.length; i++) _stageRow(i, current),
            if (widget.bubble.progressLine != null) ...[
              const SizedBox(height: 6),
              Padding(
                padding: const EdgeInsets.only(left: 30),
                child: Text(widget.bubble.progressLine!,
                    style: const TextStyle(fontSize: 13, color: AppColors.textSoft)),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _stageRow(int i, int current) {
    final done = i < current;
    final active = i == current;
    final label = widget.i18n.stageLabel(searchStages[i]);

    Widget mark;
    if (done) {
      mark = const Icon(Icons.check_circle, size: 20, color: AppColors.green);
    } else if (active) {
      mark = const SizedBox(
          width: 18,
          height: 18,
          child: CircularProgressIndicator(strokeWidth: 2.2, color: AppColors.green));
    } else {
      mark = Icon(Icons.circle_outlined, size: 20, color: AppColors.green.withValues(alpha: 0.25));
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(children: [
        SizedBox(width: 22, height: 22, child: Center(child: mark)),
        const SizedBox(width: 10),
        Text(
          done || active ? '$label…' : label,
          style: TextStyle(
            fontSize: 14.5,
            height: 1.1,
            color: (done || active) ? AppColors.slate : AppColors.textFaint,
            fontWeight: active ? FontWeight.w600 : FontWeight.w400,
          ),
        ),
      ]),
    );
  }
}
