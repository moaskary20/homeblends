import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../shared/models/product.dart';
import '../../shared/models/review.dart';
import '../../shared/widgets/product_card.dart';
import '../../shared/widgets/state_views.dart';
import '../cart/cart_provider.dart';

class ProductDetailScreen extends ConsumerStatefulWidget {
  const ProductDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  ConsumerState<ProductDetailScreen> createState() =>
      _ProductDetailScreenState();
}

class _ProductDetailScreenState extends ConsumerState<ProductDetailScreen> {
  Product? _product;
  List<Review> _reviews = [];
  ProductVariant? _selectedVariant;
  int _quantity = 1;
  bool _loading = true;
  String? _error;
  bool _adding = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final product = await catalog.getProduct(widget.slug);
      final reviews = await catalog.getReviews(widget.slug);
      if (!mounted) return;
      setState(() {
        _product = product;
        _reviews = reviews;
        _selectedVariant = product.variants.isNotEmpty
            ? product.variants.firstWhere(
                (v) => v.isDefault,
                orElse: () => product.variants.first,
              )
            : null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _addToCart() async {
    final product = _product;
    if (product == null) return;
    setState(() => _adding = true);
    try {
      await ref.read(cartProvider.notifier).addItem(
            productId: product.id,
            variantId: _selectedVariant?.id,
            quantity: _quantity,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تمت الإضافة إلى السلة')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: LoadingView());
    }
    if (_error != null) {
      return Scaffold(
        appBar: AppBar(),
        body: ErrorView(message: _error!, onRetry: _load),
      );
    }

    final product = _product!;
    final images = product.images.isNotEmpty
        ? product.images
        : (product.imageUrl != null
            ? [ProductImage(id: 0, url: product.imageUrl!)]
            : <ProductImage>[]);

    return Scaffold(
      appBar: AppBar(title: Text(product.name)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (images.isNotEmpty)
            AspectRatio(
              aspectRatio: 1,
              child: CachedNetworkImage(
                imageUrl: images.first.url,
                fit: BoxFit.cover,
              ),
            ),
          const SizedBox(height: 16),
          Text(
            product.name,
            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          PriceTag(
            price: product.displayPrice,
            originalPrice:
                product.hasActiveDiscount ? product.regularPrice : null,
          ),
          if (product.shortDescription != null) ...[
            const SizedBox(height: 12),
            Text(product.shortDescription!),
          ],
          if (product.variants.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text('الخيارات', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: product.variants.map((variant) {
                final selected = _selectedVariant?.id == variant.id;
                return ChoiceChip(
                  label: Text(variant.sku),
                  selected: selected,
                  onSelected: (_) =>
                      setState(() => _selectedVariant = variant),
                );
              }).toList(),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              const Text('الكمية: '),
              IconButton(
                onPressed: _quantity > 1
                    ? () => setState(() => _quantity--)
                    : null,
                icon: const Icon(Icons.remove_circle_outline),
              ),
              Text('$_quantity'),
              IconButton(
                onPressed: () => setState(() => _quantity++),
                icon: const Icon(Icons.add_circle_outline),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _adding ? null : _addToCart,
              icon: _adding
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.shopping_cart),
              label: const Text('أضف إلى السلة'),
            ),
          ),
          if (_reviews.isNotEmpty) ...[
            const SizedBox(height: 24),
            const Text(
              'التقييمات',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            ..._reviews.map(
              (review) => Card(
                child: ListTile(
                  title: Row(
                    children: [
                      ...List.generate(
                        5,
                        (i) => Icon(
                          i < review.rating
                              ? Icons.star
                              : Icons.star_border,
                          color: AppColors.primary,
                          size: 18,
                        ),
                      ),
                      if (review.userName != null) ...[
                        const SizedBox(width: 8),
                        Text(review.userName!),
                      ],
                    ],
                  ),
                  subtitle: review.comment != null
                      ? Text(review.comment!)
                      : null,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
