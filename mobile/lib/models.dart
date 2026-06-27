// Data models mirroring the backend chat API payloads.

class Choice {
  final String value;
  final String label;
  const Choice({required this.value, required this.label});

  factory Choice.fromJson(Map<String, dynamic> j) =>
      Choice(value: '${j['value']}', label: '${j['label']}');
}

class CardField {
  final String type; // counter | toggle
  final String key;
  final String label;
  final String? sublabel;
  final int min;
  final int max;
  final int intValue;
  final bool boolValue;

  const CardField({
    required this.type,
    required this.key,
    required this.label,
    this.sublabel,
    this.min = 0,
    this.max = 99,
    this.intValue = 0,
    this.boolValue = false,
  });

  factory CardField.fromJson(Map<String, dynamic> j) => CardField(
        type: '${j['type']}',
        key: '${j['key']}',
        label: '${j['label']}',
        sublabel: j['sublabel']?.toString(),
        min: (j['min'] as num?)?.toInt() ?? 0,
        max: (j['max'] as num?)?.toInt() ?? 99,
        intValue: (j['value'] is num) ? (j['value'] as num).toInt() : 0,
        boolValue: j['value'] == true,
      );
}

class ChatCard {
  final String key;
  final String icon;
  final String title;
  final String? subtitle;
  final String? select; // single | multi | null
  final List<Choice> choices;
  final List<CardField> fields;
  final String? continueLabel;
  final bool allowSkip;
  final String? skipLabel;

  const ChatCard({
    required this.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.select,
    this.choices = const [],
    this.fields = const [],
    this.continueLabel,
    this.allowSkip = false,
    this.skipLabel,
  });

  bool get isSingleSelect => select == 'single';
  bool get isMultiSelect => select == 'multi';
  bool get isForm => fields.isNotEmpty;

  factory ChatCard.fromJson(Map<String, dynamic> j) => ChatCard(
        key: '${j['key']}',
        icon: '${j['icon'] ?? ''}',
        title: '${j['title'] ?? ''}',
        subtitle: j['subtitle']?.toString(),
        select: j['select']?.toString(),
        choices: ((j['choices'] as List?) ?? [])
            .map((e) => Choice.fromJson(e as Map<String, dynamic>))
            .toList(),
        fields: ((j['fields'] as List?) ?? [])
            .map((e) => CardField.fromJson(e as Map<String, dynamic>))
            .toList(),
        continueLabel: j['continueLabel']?.toString(),
        allowSkip: j['allowSkip'] == true,
        skipLabel: j['skipLabel']?.toString(),
      );
}

class ChatResult {
  final String title;
  final int? price;
  final String currency;
  final int? rooms;
  final num? area;
  final String? district;
  final String? region;
  final String? source;
  final String? url;
  final String? nearMetro;
  final List<String> images;
  final int score;

  const ChatResult({
    required this.title,
    this.price,
    this.currency = 'USD',
    this.rooms,
    this.area,
    this.district,
    this.region,
    this.source,
    this.url,
    this.nearMetro,
    this.images = const [],
    this.score = 0,
  });

  factory ChatResult.fromJson(Map<String, dynamic> j) => ChatResult(
        title: '${j['title'] ?? ''}',
        price: (j['price'] as num?)?.toInt(),
        currency: '${j['currency'] ?? 'USD'}',
        rooms: (j['rooms'] as num?)?.toInt(),
        area: j['area'] as num?,
        district: j['district']?.toString(),
        region: j['region']?.toString(),
        source: j['source']?.toString(),
        url: j['url']?.toString(),
        nearMetro: j['near_metro']?.toString(),
        images: ((j['images'] as List?) ?? []).map((e) => '$e').toList(),
        score: (j['score'] as num?)?.toInt() ?? 0,
      );
}

/// A response from /chat/card or /chat/send.
class ChatResponse {
  final String reply;
  final ChatCard? card;
  final Map<String, dynamic> criteria;
  final bool ready;
  final String status; // asking | searching | done
  final int? searchId;

  const ChatResponse({
    required this.reply,
    this.card,
    this.criteria = const {},
    this.ready = false,
    this.status = 'asking',
    this.searchId,
  });

  factory ChatResponse.fromJson(Map<String, dynamic> j) => ChatResponse(
        reply: '${j['reply'] ?? ''}',
        card: j['card'] is Map<String, dynamic>
            ? ChatCard.fromJson(j['card'] as Map<String, dynamic>)
            : null,
        // The backend serialises empty criteria as a JSON array ([]); accept both
        // shapes so it never throws on the Map cast.
        criteria: j['criteria'] is Map
            ? Map<String, dynamic>.from(j['criteria'] as Map)
            : <String, dynamic>{},
        ready: j['ready'] == true,
        status: '${j['status'] ?? 'asking'}',
        searchId: (j['search_id'] as num?)?.toInt(),
      );
}

/// A response from /chat/status/{search}.
class SearchStatus {
  final String status;
  final String stage; // searching | checking | contacting | waiting | done
  final int progress;
  final int contacted;
  final int agreed;
  final bool done;
  final List<ChatResult> results;
  final String? summary;

  const SearchStatus({
    required this.status,
    this.stage = 'searching',
    this.progress = 0,
    this.contacted = 0,
    this.agreed = 0,
    this.done = false,
    this.results = const [],
    this.summary,
  });

  factory SearchStatus.fromJson(Map<String, dynamic> j) => SearchStatus(
        status: '${j['status'] ?? ''}',
        stage: '${j['stage'] ?? 'searching'}',
        progress: (j['progress'] as num?)?.toInt() ?? 0,
        contacted: (j['contacted'] as num?)?.toInt() ?? 0,
        agreed: (j['agreed'] as num?)?.toInt() ?? 0,
        done: j['done'] == true,
        results: ((j['results'] as List?) ?? [])
            .map((e) => ChatResult.fromJson(e as Map<String, dynamic>))
            .toList(),
        summary: j['summary']?.toString(),
      );
}

/// The fixed ordered stages shown in the live search checklist.
const searchStages = ['searching', 'checking', 'contacting', 'waiting', 'done'];
