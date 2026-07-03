import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'env.dart';

final apiBaseUrlProvider = StateProvider<String>((ref) => ApiBaseUrl.current);

/// Runtime API base URL (saved override or platform default).
class ApiBaseUrl {
  static const _prefsKey = 'api_base_url';
  static String? _saved;

  static Future<void> loadSaved() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null && saved.trim().isNotEmpty) {
      _saved = _normalize(saved.trim());
    }
  }

  static String get current => _saved ?? Env.defaultApiBaseUrl;

  static Future<void> save(String url) async {
    final normalized = _normalize(url.trim());
    _saved = normalized;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, normalized);
  }

  static Future<void> clear() async {
    _saved = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKey);
  }

  static String _normalize(String url) {
    var value = url.trim();
    while (value.endsWith('/')) {
      value = value.substring(0, value.length - 1);
    }
    if (value.endsWith('/api/v1')) {
      return value;
    }
    if (value.endsWith('/api')) {
      return '$value/v1';
    }
    return '$value/api/v1';
  }
}
