import '../../core/utils/media_url.dart';
import 'category.dart';

class FlashSaleInfo {
  const FlashSaleInfo({
    required this.flashPrice,
    this.comparePrice,
    this.discountPercent,
    this.endsAt,
    this.remaining,
  });

  factory FlashSaleInfo.fromJson(Map<String, dynamic> json) => FlashSaleInfo(
        flashPrice: _toNum(json['flash_price']),
        comparePrice: _toNum(json['compare_price']),
        discountPercent: _toNum(json['discount_percent']),
        endsAt: json['ends_at'] as String?,
        remaining: json['remaining'] as int?,
      );

  final num? flashPrice;
  final num? comparePrice;
  final num? discountPercent;
  final String? endsAt;
  final int? remaining;
}

class ProductVariant {
  const ProductVariant({
    required this.id,
    required this.sku,
    required this.price,
    required this.stockQuantity,
    this.image,
    this.isDefault = false,
  });

  factory ProductVariant.fromJson(Map<String, dynamic> json) => ProductVariant(
        id: json['id'] as int,
        sku: json['sku'] as String,
        price: _toNum(json['price']) ?? 0,
        stockQuantity: json['stock_quantity'] as int? ?? 0,
        image: MediaUrl.resolve(json['image'] as String?),
        isDefault: json['is_default'] as bool? ?? false,
      );

  final int id;
  final String sku;
  final num price;
  final int stockQuantity;
  final String? image;
  final bool isDefault;
}

class ProductImage {
  const ProductImage({
    required this.id,
    required this.url,
    this.alt,
  });

  factory ProductImage.fromJson(Map<String, dynamic> json) => ProductImage(
        id: json['id'] as int,
        url: MediaUrl.resolve((json['url'] ?? json['path']) as String?) ?? '',
        alt: json['alt'] as String?,
      );

  final int id;
  final String url;
  final String? alt;
}

class Product {
  const Product({
    required this.id,
    required this.name,
    required this.slug,
    this.sku,
    this.shortDescription,
    this.mainImage,
    this.regularPrice,
    this.discountPrice,
    this.effectivePrice,
    this.hasActiveDiscount = false,
    this.isFlashSale = false,
    this.flashSale,
    this.stockQuantity = 0,
    this.isFeatured = false,
    this.avgRating,
    this.reviewsCount = 0,
    this.category,
    this.images = const [],
    this.variants = const [],
  });

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        name: json['name'] as String,
        slug: json['slug'] as String,
        sku: json['sku'] as String?,
        shortDescription: json['short_description'] as String?,
        mainImage: MediaUrl.resolve(json['main_image'] as String?),
        regularPrice: _toNum(json['regular_price']),
        discountPrice: _toNum(json['discount_price']),
        effectivePrice: _toNum(json['effective_price']),
        hasActiveDiscount: json['has_active_discount'] as bool? ?? false,
        isFlashSale: json['is_flash_sale'] as bool? ?? false,
        flashSale: json['flash_sale'] != null
            ? FlashSaleInfo.fromJson(json['flash_sale'] as Map<String, dynamic>)
            : null,
        stockQuantity: json['stock_quantity'] as int? ?? 0,
        isFeatured: json['is_featured'] as bool? ?? false,
        avgRating: _toNum(json['avg_rating']),
        reviewsCount: json['reviews_count'] as int? ?? 0,
        category: json['category'] != null
            ? Category.fromJson(json['category'] as Map<String, dynamic>)
            : null,
        images: (json['images'] as List<dynamic>? ?? [])
            .map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
            .toList(),
        variants: (json['variants'] as List<dynamic>? ?? [])
            .map((e) => ProductVariant.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  final int id;
  final String name;
  final String slug;
  final String? sku;
  final String? shortDescription;
  final String? mainImage;
  final num? regularPrice;
  final num? discountPrice;
  final num? effectivePrice;
  final bool hasActiveDiscount;
  final bool isFlashSale;
  final FlashSaleInfo? flashSale;
  final int stockQuantity;
  final bool isFeatured;
  final num? avgRating;
  final int reviewsCount;
  final Category? category;
  final List<ProductImage> images;
  final List<ProductVariant> variants;

  num get displayPrice => effectivePrice ?? regularPrice ?? 0;
  String? get imageUrl => mainImage ?? (images.isNotEmpty ? images.first.url : null);
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
