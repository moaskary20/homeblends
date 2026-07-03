class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;

  @override
  String toString() => message;
}

ApiException parseApiException(dynamic error) {
  if (error is ApiException) return error;
  return ApiException('حدث خطأ غير متوقع');
}
