class SessionManager {
  static Future<void> Function()? onUnauthorized;

  static Future<void> handleUnauthorized() async {
    await onUnauthorized?.call();
  }
}
