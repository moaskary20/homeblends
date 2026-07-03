import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/models/product.dart';

class ProductCard extends StatelessWidget {
  const ProductCard({super.key, required this.product});

  final Product product;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => context.push('/products/${product.slug}'),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: product.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: product.imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => const ColoredBox(
                        color: AppColors.background,
                        child: Center(child: CircularProgressIndicator()),
                      ),
                      errorWidget: (_, __, ___) => const ColoredBox(
                        color: AppColors.background,
                        child: Icon(Icons.image_not_supported),
                      ),
                    )
                  : const ColoredBox(
                      color: AppColors.background,
                      child: Icon(Icons.image, size: 40),
                    ),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 6),
                  PriceTag(
                    price: product.displayPrice,
                    originalPrice: product.hasActiveDiscount
                        ? product.regularPrice
                        : null,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class PriceTag extends StatelessWidget {
  const PriceTag({super.key, required this.price, this.originalPrice});

  final num price;
  final num? originalPrice;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          formatPrice(price),
          style: const TextStyle(
            color: AppColors.primary,
            fontWeight: FontWeight.bold,
            fontSize: 15,
          ),
        ),
        if (originalPrice != null && originalPrice! > price) ...[
          const SizedBox(width: 8),
          Text(
            formatPrice(originalPrice),
            style: const TextStyle(
              color: AppColors.muted,
              decoration: TextDecoration.lineThrough,
              fontSize: 12,
            ),
          ),
        ],
      ],
    );
  }
}
