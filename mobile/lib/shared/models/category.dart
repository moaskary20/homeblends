import '../../core/utils/media_url.dart';

class Category {
  const Category({
    required this.id,
    required this.name,
    required this.slug,
    this.image,
    this.children = const [],
  });

  factory Category.fromJson(Map<String, dynamic> json) => Category(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String,
        slug: json['slug'] as String,
        image: MediaUrl.resolve(json['image'] as String?),
        children: _parseChildren(json['children']),
      );

  static List<Category> _parseChildren(dynamic raw) {
    if (raw is List) {
      return raw
          .map((e) => Category.fromJson(e as Map<String, dynamic>))
          .toList();
    }
    if (raw is Map<String, dynamic> && raw['data'] is List) {
      return (raw['data'] as List)
          .map((e) => Category.fromJson(e as Map<String, dynamic>))
          .toList();
    }
    return [];
  }

  final int id;
  final String name;
  final String slug;
  final String? image;
  final List<Category> children;
}
