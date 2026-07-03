import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/widgets/state_views.dart';
import '../auth/auth_provider.dart';
import 'cart_provider.dart';

class CartScreen extends ConsumerStatefulWidget {
  const CartScreen({super.key});

  @override
  ConsumerState<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends ConsumerState<CartScreen> {
  final _couponController = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(cartProvider.notifier).load());
  }

  @override
  void dispose() {
    _couponController.dispose();
    super.dispose();
  }

  Future<void> _applyCoupon() async {
    final code = _couponController.text.trim();
    if (code.isEmpty) return;
    try {
      await ref.read(cartProvider.notifier).applyCoupon(code);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تطبيق الكوبون')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cartState = ref.watch(cartProvider);
    final isAuth = ref.watch(isAuthenticatedProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('السلة')),
      body: cartState.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          message: e.toString(),
          onRetry: () => ref.read(cartProvider.notifier).load(),
        ),
        data: (response) {
          if (response == null || response.cart.items.isEmpty) {
            return const EmptyView(
              message: 'السلة فارغة',
              icon: Icons.shopping_cart_outlined,
            );
          }

          return Column(
            children: [
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: response.cart.items.length,
                  itemBuilder: (context, index) {
                    final item = response.cart.items[index];
                    final product = item.product;
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Row(
                          children: [
                            if (product?.imageUrl != null)
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: CachedNetworkImage(
                                  imageUrl: product!.imageUrl!,
                                  width: 72,
                                  height: 72,
                                  fit: BoxFit.cover,
                                ),
                              ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    product?.name ?? 'منتج',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(formatPrice(item.unitPrice)),
                                  Row(
                                    children: [
                                      IconButton(
                                        onPressed: item.quantity > 1
                                            ? () => ref
                                                .read(cartProvider.notifier)
                                                .updateQuantity(
                                                  item.id,
                                                  item.quantity - 1,
                                                )
                                            : null,
                                        icon: const Icon(
                                          Icons.remove_circle_outline,
                                        ),
                                      ),
                                      Text('${item.quantity}'),
                                      IconButton(
                                        onPressed: () => ref
                                            .read(cartProvider.notifier)
                                            .updateQuantity(
                                              item.id,
                                              item.quantity + 1,
                                            ),
                                        icon: const Icon(
                                          Icons.add_circle_outline,
                                        ),
                                      ),
                                      const Spacer(),
                                      IconButton(
                                        onPressed: () => ref
                                            .read(cartProvider.notifier)
                                            .removeItem(item.id),
                                        icon: const Icon(
                                          Icons.delete_outline,
                                          color: AppColors.error,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: const BoxDecoration(
                  color: AppColors.surface,
                  border: Border(top: BorderSide(color: AppColors.border)),
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _couponController,
                            decoration: const InputDecoration(
                              hintText: 'كود الخصم',
                              isDense: true,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        OutlinedButton(
                          onPressed: _applyCoupon,
                          child: const Text('تطبيق'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('الإجمالي'),
                        Text(
                          formatPrice(response.totals.subtotal),
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                            color: AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () {
                          if (isAuth) {
                            context.push('/checkout');
                          } else {
                            context.push('/login?redirect=/checkout');
                          }
                        },
                        child: const Text('إتمام الشراء'),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
