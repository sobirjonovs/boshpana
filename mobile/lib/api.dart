import 'dart:convert';
import 'dart:io' show Platform;

import 'package:http/http.dart' as http;

import 'models.dart';

/// Talks to the stateless Boshpana chat API (POST card/send, GET status).
///
/// The client holds the `criteria` and passes it on every call — no cookies,
/// no CSRF. Base URL auto-targets the host machine from emulators; override
/// [ChatApi.baseUrl] for a real device (use your computer's LAN IP) or prod.
class ChatApi {
  ChatApi({String? baseUrl}) : baseUrl = baseUrl ?? _defaultBaseUrl();

  final String baseUrl;

  /// Backend host. With `adb reverse tcp:8088 tcp:8088` a USB-connected phone
  /// reaches the computer's backend via 127.0.0.1 (no Wi-Fi dependency). For a
  /// Wi-Fi-only device set this to the computer's LAN IP instead (e.g.
  /// '172.16.8.88'). Leave '' to auto-target emulators.
  static const String lanBackendIp = '172.16.8.88';

  static String _defaultBaseUrl() {
    if (lanBackendIp.isNotEmpty) {
      return 'http://$lanBackendIp:8088/api/v1';
    }
    // Android emulator reaches the host via 10.0.2.2; iOS sim via 127.0.0.1.
    try {
      if (Platform.isAndroid) return 'http://10.0.2.2:8088/api/v1';
    } catch (_) {/* web/desktop */}
    return 'http://127.0.0.1:8088/api/v1';
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  /// Retry transient failures (network blips, backend restarts) before giving up.
  Future<T> _retry<T>(Future<T> Function() fn, {int tries = 3}) async {
    Object? last;
    for (var i = 0; i < tries; i++) {
      try {
        return await fn();
      } catch (e) {
        last = e;
        await Future<void>.delayed(Duration(milliseconds: 400 * (i + 1)));
      }
    }
    throw last ?? Exception('request failed');
  }

  Future<ChatResponse> card({
    required Map<String, dynamic> criteria,
    required String lang,
  }) {
    return _retry(() async {
      final r = await http
          .post(Uri.parse('$baseUrl/chat/card'),
              headers: _headers,
              body: jsonEncode({'criteria': criteria, 'lang': lang}))
          .timeout(const Duration(seconds: 30));
      return ChatResponse.fromJson(_decode(r));
    });
  }

  Future<ChatResponse> send({
    required String message,
    required String lang,
    required Map<String, dynamic> criteria,
    String? field,
    Object? value,
  }) async {
    final body = <String, dynamic>{
      'message': message,
      'lang': lang,
      'criteria': criteria,
    };
    if (field != null) {
      body['field'] = field;
      body['value'] = value;
    }
    return _retry(() async {
      final r = await http
          .post(Uri.parse('$baseUrl/chat/send'),
              headers: _headers, body: jsonEncode(body))
          .timeout(const Duration(seconds: 45));
      return ChatResponse.fromJson(_decode(r));
    });
  }

  Future<SearchStatus> status(int searchId, String lang) {
    return _retry(() async {
      final r = await http
          .get(Uri.parse('$baseUrl/chat/status/$searchId?lang=$lang'),
              headers: _headers)
          .timeout(const Duration(seconds: 30));
      return SearchStatus.fromJson(_decode(r));
    });
  }

  Map<String, dynamic> _decode(http.Response r) {
    if (r.statusCode >= 400) {
      throw Exception('API ${r.statusCode}: ${r.body}');
    }
    return jsonDecode(r.body) as Map<String, dynamic>;
  }
}
