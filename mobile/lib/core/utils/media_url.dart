import '../config/api_base_url.dart';

/// Rewrites API image URLs to match the active API host (incl. port / emulator).
class MediaUrl {
  static String? resolve(String? url) {
    if (url == null || url.isEmpty) return null;

    final assetBase = _assetBaseUri();
    if (assetBase == null) return url;

    if (url.startsWith('/')) {
      return assetBase.replace(path: url, query: null, fragment: null).toString();
    }

    final parsed = Uri.tryParse(url);
    if (parsed == null) return url;

    if (_isLocalHost(parsed.host)) {
      return assetBase
          .replace(
            path: parsed.path,
            query: parsed.hasQuery ? parsed.query : null,
            fragment: null,
          )
          .toString();
    }

    return url;
  }

  static Uri? _assetBaseUri() {
    final apiUri = Uri.tryParse(ApiBaseUrl.current);
    if (apiUri == null || apiUri.host.isEmpty) return null;

    var path = apiUri.path;
    if (path.endsWith('/')) {
      path = path.substring(0, path.length - 1);
    }
    if (path.endsWith('/api/v1')) {
      path = path.substring(0, path.length - 7);
    }

    return apiUri.replace(
      path: path.isEmpty ? null : path,
      query: null,
      fragment: null,
    );
  }

  static bool _isLocalHost(String host) {
    final normalized = host.toLowerCase();
    return normalized == 'localhost' ||
        normalized == '127.0.0.1' ||
        normalized == '10.0.2.2' ||
        normalized == '::1';
  }
}
