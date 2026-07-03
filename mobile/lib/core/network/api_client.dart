import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../auth/session_manager.dart';
import '../config/env.dart';
import '../storage/session_storage.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

typedef UnauthorizedCallback = void Function();

class ApiClient {
  ApiClient({
    required TokenStorage tokenStorage,
    required SessionStorage sessionStorage,
  })  : _tokenStorage = tokenStorage,
        _sessionStorage = sessionStorage,
        _dio = Dio(
          BaseOptions(
            baseUrl: Env.apiBaseUrl,
            connectTimeout: const Duration(seconds: 30),
            receiveTimeout: const Duration(seconds: 30),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          final sessionId = await _sessionStorage.getOrCreate();
          options.headers['X-Session-Id'] = sessionId;
          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            await _tokenStorage.clear();
            await SessionManager.handleUnauthorized();
          }
          handler.next(error);
        },
      ),
    );
  }

  final Dio _dio;
  final TokenStorage _tokenStorage;
  final SessionStorage _sessionStorage;

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) =>
      _request(() => _dio.get<T>(path, queryParameters: queryParameters));

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) =>
      _request(
        () => _dio.post<T>(path, data: data, queryParameters: queryParameters),
      );

  Future<Response<T>> patch<T>(String path, {dynamic data}) =>
      _request(() => _dio.patch<T>(path, data: data));

  Future<Response<T>> put<T>(String path, {dynamic data}) =>
      _request(() => _dio.put<T>(path, data: data));

  Future<Response<T>> delete<T>(String path) =>
      _request(() => _dio.delete<T>(path));

  Future<Response<T>> _request<T>(Future<Response<T>> Function() call) async {
    try {
      return await call();
    } on DioException catch (e) {
      throw _mapDioError(e);
    }
  }

  ApiException _mapDioError(DioException error) {
    final response = error.response;
    final data = response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message']?.toString() ?? 'حدث خطأ في الطلب';
      Map<String, List<String>>? errors;
      if (data['errors'] is Map) {
        errors = (data['errors'] as Map).map(
          (key, value) => MapEntry(
            key.toString(),
            (value as List).map((e) => e.toString()).toList(),
          ),
        );
      }
      return ApiException(
        message,
        statusCode: response?.statusCode,
        errors: errors,
      );
    }
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout) {
      return ApiException('انتهت مهلة الاتصال بالخادم');
    }
    if (error.type == DioExceptionType.connectionError) {
      return ApiException('تعذر الاتصال بالخادم');
    }
    return ApiException(error.message ?? 'حدث خطأ غير متوقع');
  }
}

final secureStorageProvider = Provider(
  (_) => const FlutterSecureStorage(),
);

final tokenStorageProvider = Provider(
  (ref) => TokenStorage(ref.watch(secureStorageProvider)),
);

final sharedPreferencesProvider = FutureProvider<SharedPreferences>(
  (_) => SharedPreferences.getInstance(),
);

final sessionStorageProvider = FutureProvider<SessionStorage>((ref) async {
  final prefs = await ref.watch(sharedPreferencesProvider.future);
  return SessionStorage(prefs);
});

final apiClientProvider = FutureProvider<ApiClient>((ref) async {
  await ref.watch(sessionStorageProvider.future);
  return ApiClient(
    tokenStorage: ref.watch(tokenStorageProvider),
    sessionStorage: await ref.watch(sessionStorageProvider.future),
  );
});
