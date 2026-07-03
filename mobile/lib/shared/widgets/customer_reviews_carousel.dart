import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../models/home.dart';

class CustomerReviewsCarousel extends StatefulWidget {
  const CustomerReviewsCarousel({
    super.key,
    required this.title,
    required this.reviews,
  });

  final String title;
  final List<CustomerReviewCard> reviews;

  @override
  State<CustomerReviewsCarousel> createState() =>
      _CustomerReviewsCarouselState();
}

class _CustomerReviewsCarouselState extends State<CustomerReviewsCarousel> {
  final _controller = ScrollController();
  static const _cardWidth = 280.0;
  static const _spacing = 12.0;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _scrollBy(double delta) {
    if (!_controller.hasClients) return;
    final target = (_controller.offset + delta).clamp(
      0.0,
      _controller.position.maxScrollExtent,
    );
    _controller.animateTo(
      target,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    if (widget.reviews.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                widget.title,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            IconButton(
              onPressed: () => _scrollBy(-(_cardWidth + _spacing)),
              icon: const Icon(Icons.chevron_right),
              tooltip: 'السابق',
            ),
            IconButton(
              onPressed: () => _scrollBy(_cardWidth + _spacing),
              icon: const Icon(Icons.chevron_left),
              tooltip: 'التالي',
            ),
          ],
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 320,
          child: ListView.separated(
            controller: _controller,
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 4),
            itemCount: widget.reviews.length,
            separatorBuilder: (_, __) => const SizedBox(width: _spacing),
            itemBuilder: (_, index) {
              return SizedBox(
                width: _cardWidth,
                child: _ReviewCard(review: widget.reviews[index]),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.review});

  final CustomerReviewCard review;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (review.image != null)
            AspectRatio(
              aspectRatio: 4 / 3,
              child: CachedNetworkImage(
                imageUrl: review.image!,
                fit: BoxFit.cover,
                errorWidget: (_, __, ___) => const ColoredBox(
                  color: AppColors.background,
                  child: Icon(Icons.person, size: 48),
                ),
              ),
            ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          review.customerName,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (review.isVerified)
                        const Icon(
                          Icons.verified,
                          size: 16,
                          color: AppColors.primary,
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: List.generate(
                      5,
                      (i) => Icon(
                        i < review.rating ? Icons.star : Icons.star_border,
                        color: AppColors.primary,
                        size: 16,
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Expanded(
                    child: Text(
                      review.comment,
                      maxLines: 4,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.muted,
                        height: 1.4,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
