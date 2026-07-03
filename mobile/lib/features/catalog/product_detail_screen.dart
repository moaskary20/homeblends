import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/models/product.dart';
import '../../shared/models/review.dart';
import '../../shared/widgets/html_content.dart';
import '../../shared/widgets/product_card.dart';
import '../../shared/widgets/product_gallery.dart';
import '../../shared/widgets/state_views.dart';
import '../auth/auth_provider.dart';
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
  bool _busy = false;

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

  ProductVariant? get _activeVariant => _selectedVariant;

  num get _displayPrice {
    final variant = _activeVariant;
    if (variant != null) return variant.price;
    final product = _product;
    if (product == null) return 0;
    if (product.isFlashSale && product.flashSale?.flashPrice != null) {
      return product.flashSale!.flashPrice!;
    }
    return product.displayPrice;
  }

  num? get _comparePrice {
    final variant = _activeVariant;
    if (variant?.comparePrice != null && variant!.comparePrice! > variant.price) {
      return variant.comparePrice;
    }
    final product = _product;
    if (product == null) return null;
    if (product.isFlashSale && product.flashSale?.comparePrice != null) {
      return product.flashSale!.comparePrice;
    }
    if (product.hasActiveDiscount && product.regularPrice != null) {
      return product.regularPrice;
    }
    return null;
  }

  int get _maxQuantity {
    final variant = _activeVariant;
    if (variant != null && variant.stockQuantity > 0) {
      return variant.stockQuantity;
    }
    return _product?.stockQuantity ?? 0;
  }

  bool get _inStock => _maxQuantity > 0;

  List<GalleryImage> get _galleryImages {
    final product = _product;
    if (product == null) return [];

    final variantImage = _activeVariant?.image;
    if (variantImage != null && variantImage.isNotEmpty) {
      final base = product.galleryImages;
      if (base.any((img) => img.url == variantImage)) {
        return base;
      }
      return [
        GalleryImage(url: variantImage, alt: product.name),
        ...base.where((img) => img.url != variantImage),
      ];
    }

    return product.galleryImages;
  }

  Future<bool> _addProductToCart() async {
    final product = _product;
    if (product == null || !_inStock) return false;

    try {
      await ref.read(cartProvider.notifier).addItem(
            productId: product.id,
            variantId: _activeVariant?.id,
            quantity: _quantity,
          );
      return true;
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
      return false;
    }
  }

  Future<void> _addToCartAndGoToCart() async {
    if (_busy) return;
    setState(() => _busy = true);
    final added = await _addProductToCart();
    if (!mounted) return;
    setState(() => _busy = false);
    if (added) context.go('/cart-tab');
  }

  Future<void> _buyNow() async {
    if (_busy) return;
    setState(() => _busy = true);
    final added = await _addProductToCart();
    if (!mounted) return;
    setState(() => _busy = false);
    if (!added) return;

    final isAuth = ref.read(isAuthenticatedProvider);
    if (isAuth) {
      context.push('/checkout');
    } else {
      context.push('/login?redirect=/checkout');
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

    return Scaffold(
      appBar: AppBar(title: Text(product.name)),
      body: ListView(
        children: [
          ProductGallery(images: _galleryImages),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if (product.isFlashSale)
                      _Badge(
                        label: product.flashSale?.discountPercent != null
                            ? 'عرض خاطف −${product.flashSale!.discountPercent!.toStringAsFixed(0)}%'
                            : 'عرض خاطف',
                        color: Colors.red,
                      ),
                    if (product.hasActiveDiscount && !product.isFlashSale)
                      const _Badge(label: 'خصم', color: AppColors.primary),
                    if (product.isFeatured)
                      const _Badge(label: 'مميز', color: AppColors.primaryDark),
                    _Badge(
                      label: _inStock ? 'متوفر' : 'غير متوفر',
                      color: _inStock ? Colors.green : AppColors.error,
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  product.name,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (product.reviewsCount > 0) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      ...List.generate(
                        5,
                        (i) => Icon(
                          i < (product.avgRating ?? 0).round()
                              ? Icons.star
                              : Icons.star_border,
                          color: AppColors.primary,
                          size: 18,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '${product.avgRating?.toStringAsFixed(1) ?? '0'} (${product.reviewsCount} تقييم)',
                        style: const TextStyle(color: AppColors.muted),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 12),
                PriceTag(
                  price: _displayPrice,
                  originalPrice: _comparePrice,
                ),
                if (product.isFlashSale && product.flashSale?.remaining != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      'متبقي ${product.flashSale!.remaining} قطعة',
                      style: const TextStyle(color: Colors.orange),
                    ),
                  ),
                if (product.shortDescription != null &&
                    product.shortDescription!.trim().isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(
                    product.shortDescription!,
                    style: const TextStyle(
                      fontSize: 15,
                      height: 1.5,
                      color: AppColors.text,
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                _MetaSection(product: product, variant: _activeVariant),
                if (product.variants.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text(
                    'الخيارات',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: product.variants.map((variant) {
                      final selected = _activeVariant?.id == variant.id;
                      return ChoiceChip(
                        label: Text(
                          '${variant.sku} — ${formatPrice(variant.price)}',
                        ),
                        selected: selected,
                        onSelected: (_) => setState(() {
                          _selectedVariant = variant;
                          if (_quantity > variant.stockQuantity &&
                              variant.stockQuantity > 0) {
                            _quantity = variant.stockQuantity;
                          }
                        }),
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
                      onPressed: _inStock && _quantity < _maxQuantity
                          ? () => setState(() => _quantity++)
                          : null,
                      icon: const Icon(Icons.add_circle_outline),
                    ),
                    if (_inStock)
                      Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: Text(
                          'متوفر: $_maxQuantity',
                          style: const TextStyle(
                            color: AppColors.muted,
                            fontSize: 12,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _busy || !_inStock
                            ? null
                            : _addToCartAndGoToCart,
                        icon: _busy
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.shopping_cart_outlined),
                        label: const Text('أضف إلى السلة'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: _busy || !_inStock ? null : _buyNow,
                        icon: const Icon(Icons.flash_on_outlined),
                        label: const Text('اشتري الآن'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                const Text(
                  'وصف المنتج',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                if (product.fullDescription != null &&
                    product.fullDescription!.trim().isNotEmpty)
                  HtmlContent(html: product.fullDescription!)
                else if (product.shortDescription != null)
                  Text(product.shortDescription!)
                else
                  const Text(
                    'لا يوجد وصف لهذا المنتج',
                    style: TextStyle(color: AppColors.muted),
                  ),
                if (_reviews.isNotEmpty) ...[
                  const SizedBox(height: 24),
                  Text(
                    'التقييمات (${_reviews.length})',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ..._reviews.map(
                    (review) => Card(
                      margin: const EdgeInsets.only(bottom: 8),
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
                              Expanded(child: Text(review.userName!)),
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
          ),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontWeight: FontWeight.w600),
      ),
    );
  }
}

class _MetaSection extends StatelessWidget {
  const _MetaSection({required this.product, this.variant});

  final Product product;
  final ProductVariant? variant;

  @override
  Widget build(BuildContext context) {
    final rows = <_MetaRow>[
      if (product.sku != null)
        _MetaRow('رمز المنتج', product.sku!),
      if (variant?.sku != null && variant!.sku != product.sku)
        _MetaRow('خيار SKU', variant!.sku),
      if (product.barcode != null)
        _MetaRow('الباركود', product.barcode!),
      if (variant?.barcode != null)
        _MetaRow('باركود الخيار', variant!.barcode!),
      if (product.category != null)
        _MetaRow('التصنيف', product.category!.name),
      if (product.weight != null)
        _MetaRow('الوزن', '${product.weight} كجم'),
      if (product.dimensions != null && product.dimensions!.isNotEmpty)
        _MetaRow('الأبعاد', product.dimensions!),
      _MetaRow('المخزون', '${product.stockQuantity}'),
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: rows
              .map(
                (row) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 110,
                        child: Text(
                          row.label,
                          style: const TextStyle(
                            color: AppColors.muted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      Expanded(child: Text(row.value)),
                    ],
                  ),
                ),
              )
              .toList(),
        ),
      ),
    );
  }
}

class _MetaRow {
  const _MetaRow(this.label, this.value);

  final String label;
  final String value;
}
