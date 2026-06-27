import 'package:flutter/material.dart';

import 'i18n.dart';
import 'models.dart';
import 'theme.dart';

typedef CardSubmit = void Function(String field, Object? value, String sentence);

/// Renders one guided card (single-select chips, a stepper+toggle form, or a
/// multi-select) in the green theme — mirrors the website cards.
class CardView extends StatefulWidget {
  const CardView({
    super.key,
    required this.card,
    required this.i18n,
    required this.locked,
    required this.onSubmit,
  });

  final ChatCard card;
  final I18n i18n;
  final bool locked;
  final CardSubmit onSubmit;

  @override
  State<CardView> createState() => _CardViewState();
}

class _CardViewState extends State<CardView> {
  late final Map<String, int> _counters = {};
  late final Map<String, bool> _toggles = {};
  final Set<String> _selected = {};
  String? _tappedChip;

  @override
  void initState() {
    super.initState();
    for (final f in widget.card.fields) {
      if (f.type == 'counter') _counters[f.key] = f.intValue;
      if (f.type == 'toggle') _toggles[f.key] = f.boolValue;
    }
  }

  IconData get _icon => switch (widget.card.icon) {
        'map-pin' => Icons.location_on_outlined,
        'wallet' => Icons.account_balance_wallet_outlined,
        'user' => Icons.person_outline,
        'door' => Icons.meeting_room_outlined,
        'sparkles' => Icons.auto_awesome_outlined,
        _ => Icons.tune,
      };

  @override
  Widget build(BuildContext context) {
    final c = widget.card;
    return Opacity(
      opacity: widget.locked ? 0.6 : 1,
      child: Container(
        width: double.infinity,
        margin: const EdgeInsets.only(top: 4, bottom: 4),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
          boxShadow: const [
            BoxShadow(color: Color(0x11000000), blurRadius: 10, offset: Offset(0, 2)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [
              Container(
                width: 30,
                height: 30,
                decoration: const BoxDecoration(color: AppColors.greenSoft, shape: BoxShape.circle),
                child: Icon(_icon, size: 18, color: AppColors.green),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(c.title,
                    style: const TextStyle(
                        fontSize: 17, fontWeight: FontWeight.w600, color: AppColors.slate)),
              ),
            ]),
            if (c.subtitle != null) ...[
              const SizedBox(height: 8),
              Text(c.subtitle!, style: const TextStyle(fontSize: 13.5, color: AppColors.textSoft)),
            ],
            const SizedBox(height: 14),
            if (c.isForm) _buildForm(c),
            if (c.choices.isNotEmpty) _buildChips(c),
            _buildActions(c),
          ],
        ),
      ),
    );
  }

  // ---- single / multi select chips ----
  Widget _buildChips(ChatCard c) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: c.choices.map((ch) {
        final selected = c.isMultiSelect ? _selected.contains(ch.value) : _tappedChip == ch.value;
        return _Chip(
          label: ch.label,
          selected: selected,
          showCheck: c.isMultiSelect,
          enabled: !widget.locked,
          onTap: () {
            if (widget.locked) return;
            if (c.isMultiSelect) {
              setState(() {
                _selected.contains(ch.value) ? _selected.remove(ch.value) : _selected.add(ch.value);
              });
            } else {
              setState(() => _tappedChip = ch.value);
              _submitSingle(c, ch);
            }
          },
        );
      }).toList(),
    );
  }

  // ---- stepper + toggle form ----
  Widget _buildForm(ChatCard c) {
    final widgets = <Widget>[];
    for (var i = 0; i < c.fields.length; i++) {
      final f = c.fields[i];
      if (i > 0) {
        widgets.add(const Divider(height: 22, color: AppColors.border));
      }
      widgets.add(Row(
        children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(f.label,
                  style: const TextStyle(
                      fontSize: 15.5, fontWeight: FontWeight.w600, color: AppColors.slate)),
              if (f.sublabel != null) ...[
                const SizedBox(height: 2),
                Text(f.sublabel!, style: const TextStyle(fontSize: 13, color: AppColors.textSoft)),
              ],
            ]),
          ),
          if (f.type == 'counter') _Stepper(
            value: _counters[f.key] ?? f.min,
            min: f.min,
            max: f.max,
            enabled: !widget.locked,
            onChanged: (v) => setState(() => _counters[f.key] = v),
          ),
          if (f.type == 'toggle') Switch(
            value: _toggles[f.key] ?? false,
            activeThumbColor: Colors.white,
            activeTrackColor: AppColors.green,
            onChanged: widget.locked ? null : (v) => setState(() => _toggles[f.key] = v),
          ),
        ],
      ));
    }
    return Column(children: widgets);
  }

  // ---- continue / skip ----
  Widget _buildActions(ChatCard c) {
    final actions = <Widget>[];
    if (c.continueLabel != null) {
      actions.add(_FilledBtn(
        label: c.continueLabel!,
        enabled: !widget.locked,
        onTap: () => _submitContinue(c),
      ));
    }
    if (c.allowSkip && c.skipLabel != null) {
      actions.add(const SizedBox(width: 10));
      actions.add(_OutlineBtn(
        label: c.skipLabel!,
        enabled: !widget.locked,
        onTap: () => _submitSkip(c),
      ));
    }
    if (actions.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: Row(children: actions),
    );
  }

  // ---- submit logic ----
  void _submitSingle(ChatCard c, Choice ch) {
    final i = widget.i18n;
    final sentence = switch (c.key) {
      'region' => i.region(ch.label),
      'budget' => i.budget(ch.label),
      'rooms' => i.roomsSentence(ch.value, ch.label),
      _ => ch.label,
    };
    widget.onSubmit(c.key, ch.value, sentence);
  }

  void _submitContinue(ChatCard c) {
    if (widget.locked) return;
    if (c.key == 'household') {
      final occ = _counters['occupants'] ?? 1;
      final furnished = _toggles['furnished'] ?? false;
      widget.onSubmit('household', {'occupants': occ, 'furnished': furnished},
          widget.i18n.household(occ, furnished));
    } else if (c.isMultiSelect) {
      _submitMulti(c);
    }
  }

  void _submitMulti(ChatCard c) {
    final values = _selected.toList();
    final labels = c.choices.where((x) => _selected.contains(x.value)).map((x) => x.label).toList();
    widget.onSubmit(c.key, values, widget.i18n.mustHaves(labels));
  }

  void _submitSkip(ChatCard c) {
    if (widget.locked) return;
    widget.onSubmit(c.key, <String>[], widget.i18n.mustHaves([]));
  }
}

// ---------------------------------------------------------------- small parts

class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.selected,
    required this.onTap,
    this.showCheck = false,
    this.enabled = true,
  });
  final String label;
  final bool selected;
  final bool showCheck;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: enabled ? onTap : null,
      borderRadius: BorderRadius.circular(999),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 120),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: selected ? AppColors.green : Colors.white,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: selected ? AppColors.green : AppColors.border),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          if (showCheck) ...[
            Icon(selected ? Icons.check_circle : Icons.circle_outlined,
                size: 16, color: selected ? Colors.white : AppColors.textFaint),
            const SizedBox(width: 6),
          ],
          Text(label,
              style: TextStyle(
                  fontSize: 14,
                  color: selected ? Colors.white : AppColors.slate,
                  fontWeight: FontWeight.w500)),
        ]),
      ),
    );
  }
}

class _Stepper extends StatelessWidget {
  const _Stepper({
    required this.value,
    required this.min,
    required this.max,
    required this.onChanged,
    this.enabled = true,
  });
  final int value, min, max;
  final bool enabled;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      _round(Icons.remove, enabled && value > min, () => onChanged(value - 1)),
      Container(
        width: 36,
        alignment: Alignment.center,
        child: Text('$value',
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600, color: AppColors.slate)),
      ),
      _round(Icons.add, enabled && value < max, () => onChanged(value + 1)),
    ]);
  }

  Widget _round(IconData icon, bool on, VoidCallback tap) {
    return InkResponse(
      onTap: on ? tap : null,
      radius: 26,
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(color: AppColors.border),
          color: Colors.white,
        ),
        child: Icon(icon, size: 18, color: on ? AppColors.slate : AppColors.textFaint),
      ),
    );
  }
}

class _FilledBtn extends StatelessWidget {
  const _FilledBtn({required this.label, required this.onTap, this.enabled = true});
  final String label;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Material(
        color: enabled ? AppColors.green : AppColors.textFaint,
        borderRadius: BorderRadius.circular(999),
        child: InkWell(
          borderRadius: BorderRadius.circular(999),
          onTap: enabled ? onTap : null,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 13),
            child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              Text(label,
                  style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w600)),
              const SizedBox(width: 4),
              const Icon(Icons.chevron_right, color: Colors.white, size: 20),
            ]),
          ),
        ),
      ),
    );
  }
}

class _OutlineBtn extends StatelessWidget {
  const _OutlineBtn({required this.label, required this.onTap, this.enabled = true});
  final String label;
  final bool enabled;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(999),
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: enabled ? onTap : null,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 13),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: AppColors.border),
          ),
          child: Text(label,
              style: const TextStyle(color: AppColors.slate, fontSize: 15, fontWeight: FontWeight.w600)),
        ),
      ),
    );
  }
}
