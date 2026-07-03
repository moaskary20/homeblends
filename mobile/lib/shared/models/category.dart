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
        id: json['id'] as int,
        name: json['name'] as String,
        slug: json['slug'] as String,
        image: MediaUrl.resolve(json['image'] as String?),
        children: (json['children'] as List<dynamic>? ?? [])
            .map((e) => Category.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  final int id;
  final String name;
  final String slug;
  final String? image;
  final List<Category> children;
}
