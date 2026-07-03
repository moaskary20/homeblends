import 'package:flutter/foundation.dart' show kIsWeb;

import 'platform_api_host.dart'
    if (dart.library.io) 'platform_api_host_io.dart';

class Env {
  static const _apiBaseUrlFromDefine = String.fromEnvironment('API_BASE_URL');

  static String get apiBaseUrl {
    if (_apiBaseUrlFromDefine.isNotEmpty) {
      return _apiBaseUrlFromDefine;
    }
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api/v1';
    }
    return platformDefaultApiBaseUrl();
  }

  static const appLocale = String.fromEnvironment(
    'APP_LOCALE',
    defaultValue: 'ar',
  );
}
