import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../models/product.dart';
import 'product_card.dart';

class ProductCarousel extends StatefulWidget {
  const ProductCarousel({
    super.key,
    required this.title,
    required this.products,
    this.categorySlug,
  });

  final String title;
  final List<Product> products;
  final String? categorySlug;

  @override
  State<ProductCarousel> createState() => _ProductCarouselState();
}

class _ProductCarouselState extends State<ProductCarousel> {
  final _controller = ScrollController();
  static const _cardWidth = 168.0;
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
    if (widget.products.isEmpty) return const SizedBox.shrink();

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
              onPressed: () => _scrollBy(-(_cardWidth + _spacing) * 2),
              icon: const Icon(Icons.chevron_right),
              tooltip: 'السابق',
            ),
            IconButton(
              onPressed: () => _scrollBy((_cardWidth + _spacing) * 2),
              icon: const Icon(Icons.chevron_left),
              tooltip: 'التالي',
            ),
            if (widget.categorySlug != null)
              TextButton(
                onPressed: () => context.push(
                  '/categories/${widget.categorySlug}?all=1',
                ),
                child: const Text('عرض الكل'),
              ),
          ],
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 260,
          child: ListView.separated(
            controller: _controller,
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 4),
            itemCount: widget.products.length,
            separatorBuilder: (_, __) => const SizedBox(width: _spacing),
            itemBuilder: (_, index) {
              return SizedBox(
                width: _cardWidth,
                child: ProductCard(product: widget.products[index]),
              );
            },
          ),
        ),
      ],
    );
  }
}

class SectionHeader extends StatelessWidget {
  const SectionHeader({super.key, required this.title, this.onViewAll});

  final String title;
  final VoidCallback? onViewAll;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ),
        if (onViewAll != null)
          TextButton(onPressed: onViewAll, child: const Text('عرض الكل')),
      ],
    );
  }
}
