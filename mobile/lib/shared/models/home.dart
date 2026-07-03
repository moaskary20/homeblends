import '../../core/utils/media_url.dart';
import 'product.dart';

class HeroSlide {
  const HeroSlide({
    required this.title,
    this.subtitle,
    this.cta,
    this.url,
    this.image,
  });

  factory HeroSlide.fromJson(Map<String, dynamic> json) => HeroSlide(
        title: json['title'] as String? ?? '',
        subtitle: json['subtitle'] as String?,
        cta: json['cta'] as String?,
        url: json['url'] as String?,
        image: MediaUrl.resolve(json['image'] as String?),
      );

  final String title;
  final String? subtitle;
  final String? cta;
  final String? url;
  final String? image;
}

class CustomerReviewCard {
  const CustomerReviewCard({
    this.image,
    required this.customerName,
    required this.rating,
    required this.comment,
    this.isVerified = false,
  });

  factory CustomerReviewCard.fromJson(Map<String, dynamic> json) =>
      CustomerReviewCard(
        image: MediaUrl.resolve(json['image'] as String?),
        customerName: json['customer_name'] as String? ?? '',
        rating: json['rating'] as int? ?? 5,
        comment: json['comment'] as String? ?? '',
        isVerified: json['is_verified'] as bool? ?? false,
      );

  final String? image;
  final String customerName;
  final int rating;
  final String comment;
  final bool isVerified;
}

class DepartmentSection {
  const DepartmentSection({
    required this.title,
    required this.slug,
    required this.products,
  });

  factory DepartmentSection.fromJson(Map<String, dynamic> json) =>
      DepartmentSection(
        title: json['title'] as String,
        slug: json['slug'] as String,
        products: (json['products'] as List<dynamic>? ?? [])
            .map((e) => Product.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  final String title;
  final String slug;
  final List<Product> products;
}

class HomeContent {
  const HomeContent({
    required this.newsTicker,
    required this.heroSlides,
    required this.featured,
    required this.departments,
    required this.customerReviewsTitle,
    required this.customerReviews,
  });

  factory HomeContent.fromJson(Map<String, dynamic> json) => HomeContent(
        newsTicker: (json['news_ticker'] as List<dynamic>? ?? [])
            .map((e) => e.toString())
            .where((e) => e.trim().isNotEmpty)
            .toList(),
        heroSlides: (json['hero_slides'] as List<dynamic>? ?? [])
            .map((e) => HeroSlide.fromJson(e as Map<String, dynamic>))
            .toList(),
        featured: (json['featured'] as List<dynamic>? ?? [])
            .map((e) => Product.fromJson(e as Map<String, dynamic>))
            .toList(),
        departments: (json['departments'] as List<dynamic>? ?? [])
            .map((e) => DepartmentSection.fromJson(e as Map<String, dynamic>))
            .toList(),
        customerReviewsTitle:
            (json['customer_reviews'] as Map<String, dynamic>?)?['title']
                    as String? ??
                'آراء العملاء',
        customerReviews:
            ((json['customer_reviews'] as Map<String, dynamic>?)?['items']
                        as List<dynamic>? ??
                    [])
                .map((e) => CustomerReviewCard.fromJson(e as Map<String, dynamic>))
                .toList(),
      );

  final List<String> newsTicker;
  final List<HeroSlide> heroSlides;
  final List<Product> featured;
  final List<DepartmentSection> departments;
  final String customerReviewsTitle;
  final List<CustomerReviewCard> customerReviews;
}
