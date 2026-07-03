import '../../core/utils/media_url.dart';
import 'category.dart';

class GalleryImage {
  const GalleryImage({required this.url, this.alt});

  factory GalleryImage.fromJson(Map<String, dynamic> json) => GalleryImage(
        url: MediaUrl.resolve(json['url'] as String?) ?? '',
        alt: json['alt'] as String?,
      );

  final String url;
  final String? alt;
}

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
    this.barcode,
    this.comparePrice,
    this.image,
    this.isDefault = false,
  });

  factory ProductVariant.fromJson(Map<String, dynamic> json) => ProductVariant(
        id: json['id'] as int,
        sku: json['sku'] as String,
        price: _toNum(json['price']) ?? 0,
        stockQuantity: json['stock_quantity'] as int? ?? 0,
        barcode: json['barcode'] as String?,
        comparePrice: _toNum(json['compare_price']),
        image: MediaUrl.resolve(json['image'] as String?),
        isDefault: json['is_default'] as bool? ?? false,
      );

  final int id;
  final String sku;
  final num price;
  final int stockQuantity;
  final String? barcode;
  final num? comparePrice;
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
    this.barcode,
    this.shortDescription,
    this.fullDescription,
    this.mainImage,
    this.regularPrice,
    this.discountPrice,
    this.effectivePrice,
    this.hasActiveDiscount = false,
    this.isFlashSale = false,
    this.flashSale,
    this.stockQuantity = 0,
    this.weight,
    this.dimensions,
    this.isFeatured = false,
    this.avgRating,
    this.reviewsCount = 0,
    this.category,
    this.gallery = const [],
    this.images = const [],
    this.variants = const [],
  });

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        name: json['name'] as String,
        slug: json['slug'] as String,
        sku: json['sku'] as String?,
        barcode: json['barcode'] as String?,
        shortDescription: json['short_description'] as String?,
        fullDescription: json['full_description'] as String?,
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
        weight: _toNum(json['weight']),
        dimensions: json['dimensions'] as String?,
        isFeatured: json['is_featured'] as bool? ?? false,
        avgRating: _toNum(json['avg_rating']),
        reviewsCount: json['reviews_count'] as int? ?? 0,
        category: json['category'] != null
            ? Category.fromJson(json['category'] as Map<String, dynamic>)
            : null,
        gallery: _parseGallery(json['gallery']),
        images: _parseImages(json['images']),
        variants: _parseVariants(json['variants']),
      );

  final int id;
  final String name;
  final String slug;
  final String? sku;
  final String? barcode;
  final String? shortDescription;
  final String? fullDescription;
  final String? mainImage;
  final num? regularPrice;
  final num? discountPrice;
  final num? effectivePrice;
  final bool hasActiveDiscount;
  final bool isFlashSale;
  final FlashSaleInfo? flashSale;
  final int stockQuantity;
  final num? weight;
  final String? dimensions;
  final bool isFeatured;
  final num? avgRating;
  final int reviewsCount;
  final Category? category;
  final List<GalleryImage> gallery;
  final List<ProductImage> images;
  final List<ProductVariant> variants;

  bool get inStock => stockQuantity > 0;

  num get displayPrice => effectivePrice ?? regularPrice ?? 0;

  String? get imageUrl =>
      mainImage ?? (gallery.isNotEmpty ? gallery.first.url : null);

  List<GalleryImage> get galleryImages {
    if (gallery.isNotEmpty) {
      return gallery.where((img) => img.url.isNotEmpty).toList();
    }

    final items = <GalleryImage>[];
    final seen = <String>{};

    if (mainImage != null && mainImage!.isNotEmpty) {
      items.add(GalleryImage(url: mainImage!, alt: name));
      seen.add(mainImage!);
    }

    for (final image in images) {
      if (image.url.isNotEmpty && !seen.contains(image.url)) {
        items.add(GalleryImage(url: image.url, alt: image.alt ?? name));
        seen.add(image.url);
      }
    }

    return items;
  }
}

List<GalleryImage> _parseGallery(dynamic raw) {
  if (raw is List) {
    return raw
        .map((e) => GalleryImage.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  if (raw is Map<String, dynamic> && raw['data'] is List) {
    return (raw['data'] as List)
        .map((e) => GalleryImage.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  return [];
}

List<ProductImage> _parseImages(dynamic raw) {
  if (raw is List) {
    return raw
        .map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  if (raw is Map<String, dynamic> && raw['data'] is List) {
    return (raw['data'] as List)
        .map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  return [];
}

List<ProductVariant> _parseVariants(dynamic raw) {
  if (raw is List) {
    return raw
        .map((e) => ProductVariant.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  if (raw is Map<String, dynamic> && raw['data'] is List) {
    return (raw['data'] as List)
        .map((e) => ProductVariant.fromJson(e as Map<String, dynamic>))
        .toList();
  }
  return [];
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
