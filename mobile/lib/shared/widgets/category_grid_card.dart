import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../core/config/api_base_url.dart';
import '../../core/theme/app_colors.dart';
import '../models/category.dart';

class CategoryGridCard extends StatelessWidget {
  const CategoryGridCard({
    super.key,
    required this.category,
    required this.onTap,
    this.compact = false,
  });

  final Category category;
  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final imageUrl = CategoryImageUrl.resolve(category);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(compact ? 18 : 22),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(compact ? 18 : 22),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: compact ? 12 : 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(compact ? 18 : 22),
            child: AspectRatio(
              aspectRatio: compact ? 0.82 : 0.78,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  _CategoryPhoto(
                    imageUrl: imageUrl,
                    name: category.name,
                  ),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.bottomCenter,
                        end: Alignment.topCenter,
                        colors: [
                          Colors.black.withValues(alpha: 0.78),
                          Colors.black.withValues(alpha: 0.18),
                          Colors.transparent,
                        ],
                        stops: const [0.0, 0.42, 0.8],
                      ),
                    ),
                  ),
                  Positioned(
                    left: 12,
                    right: 12,
                    bottom: 12,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          category.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: compact ? 14 : 16,
                            fontWeight: FontWeight.w800,
                            height: 1.25,
                            shadows: const [
                              Shadow(
                                color: Color(0x80000000),
                                offset: Offset(0, 1),
                                blurRadius: 4,
                              ),
                            ],
                          ),
                        ),
                        if (category.children.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            '${category.children.length} أقسام فرعية',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.88),
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class CategoryHeroBanner extends StatelessWidget {
  const CategoryHeroBanner({
    super.key,
    required this.category,
    this.subtitle,
  });

  final Category category;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final imageUrl = CategoryImageUrl.resolve(category);

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 4),
      height: 168,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.12),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: Stack(
          fit: StackFit.expand,
          children: [
            _CategoryPhoto(imageUrl: imageUrl, name: category.name),
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.centerRight,
                  colors: [
                    Colors.black.withValues(alpha: 0.75),
                    Colors.black.withValues(alpha: 0.25),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Text(
                    category.name,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (subtitle != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      subtitle!,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.88),
                        fontSize: 13,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class CategoryImageUrl {
  static String? resolve(Category category) {
    if (category.image != null && category.image!.isNotEmpty) {
      return category.image;
    }

    final assetBase = _assetBaseUri();
    if (assetBase == null) return null;

    return assetBase
        .replace(
          path: '/images/categories/${category.slug}.jpg',
          query: null,
          fragment: null,
        )
        .toString();
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

    return apiUri.replace(path: path.isEmpty ? null : path, query: null, fragment: null);
  }
}

class _CategoryPhoto extends StatelessWidget {
  const _CategoryPhoto({
    required this.imageUrl,
    required this.name,
  });

  final String? imageUrl;
  final String name;

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.isEmpty) {
      return _FallbackPhoto(name: name);
    }

    final isVector = imageUrl!.toLowerCase().endsWith('.svg');

    return CachedNetworkImage(
      imageUrl: imageUrl!,
      fit: isVector ? BoxFit.contain : BoxFit.cover,
      placeholder: (_, __) => const ColoredBox(
        color: AppColors.background,
        child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
      ),
      errorWidget: (_, __, ___) => _FallbackPhoto(name: name),
    );
  }
}

class _FallbackPhoto extends StatelessWidget {
  const _FallbackPhoto({required this.name});

  final String name;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFFFFF7ED), AppColors.background],
        ),
      ),
      alignment: Alignment.center,
      child: Text(
        name.isNotEmpty ? name[0] : '?',
        style: const TextStyle(
          fontSize: 42,
          fontWeight: FontWeight.bold,
          color: AppColors.primaryDark,
        ),
      ),
    );
  }
}
