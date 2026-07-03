class PaginatedResponse<T> {
  PaginatedResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJsonT,
  ) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};
    return PaginatedResponse(
      data: (json['data'] as List<dynamic>? ?? [])
          .map((e) => fromJsonT(e as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
      total: meta['total'] as int? ?? 0,
    );
  }

  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;
}

List<T> parseList<T>(
  dynamic json,
  T Function(Map<String, dynamic>) fromJsonT,
) {
  if (json is Map<String, dynamic> && json['data'] is List) {
    return (json['data'] as List)
        .map((e) => fromJsonT(e as Map<String, dynamic>))
        .toList();
  }
  if (json is List) {
    return json.map((e) => fromJsonT(e as Map<String, dynamic>)).toList();
  }
  return [];
}

T parseData<T>(dynamic json, T Function(Map<String, dynamic>) fromJsonT) {
  if (json is Map<String, dynamic> && json['data'] is Map<String, dynamic>) {
    return fromJsonT(json['data'] as Map<String, dynamic>);
  }
  if (json is Map<String, dynamic>) {
    return fromJsonT(json);
  }
  throw const FormatException('Unexpected JSON shape');
}
