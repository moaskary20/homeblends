import 'dart:io' show Platform;

import 'platform_api_host.dart' as stub;

String platformDefaultApiBaseUrl() {
  if (Platform.isAndroid) {
    return 'http://10.0.2.2:8000/api/v1';
  }
  return stub.platformDefaultApiBaseUrl();
}
