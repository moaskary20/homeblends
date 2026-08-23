class Env {
  static const appName = 'home blend';

  static const productionApiBaseUrl = 'https://homeblendstore.com/api/v1';

  static const _apiBaseUrlFromDefine = String.fromEnvironment('API_BASE_URL');

  static String get defaultApiBaseUrl {
    if (_apiBaseUrlFromDefine.isNotEmpty) {
      return _apiBaseUrlFromDefine;
    }
    return productionApiBaseUrl;
  }

  static String get apiBaseUrl => defaultApiBaseUrl;

  static const appLocale = String.fromEnvironment(
    'APP_LOCALE',
    defaultValue: 'ar',
  );
}
