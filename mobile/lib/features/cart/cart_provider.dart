import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers/repositories.dart';
import '../../shared/models/cart.dart';

class CartNotifier extends StateNotifier<AsyncValue<CartResponse?>> {
  CartNotifier(this._ref) : super(const AsyncValue.data(null));

  final Ref _ref;

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final repo = await _ref.read(cartRepositoryProvider.future);
      final cart = await repo.getCart();
      state = AsyncValue.data(cart);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> addItem({
    required int productId,
    int? variantId,
    int quantity = 1,
  }) async {
    final repo = await _ref.read(cartRepositoryProvider.future);
    final cart = await repo.addItem(
      productId: productId,
      variantId: variantId,
      quantity: quantity,
    );
    state = AsyncValue.data(cart);
  }

  Future<void> updateQuantity(int itemId, int quantity) async {
    final repo = await _ref.read(cartRepositoryProvider.future);
    final cart = await repo.updateItem(itemId, quantity);
    state = AsyncValue.data(cart);
  }

  Future<void> removeItem(int itemId) async {
    final repo = await _ref.read(cartRepositoryProvider.future);
    final cart = await repo.removeItem(itemId);
    state = AsyncValue.data(cart);
  }

  Future<void> applyCoupon(String code) async {
    final repo = await _ref.read(cartRepositoryProvider.future);
    final cart = await repo.applyCoupon(code);
    state = AsyncValue.data(cart);
  }
}

final cartProvider =
    StateNotifierProvider<CartNotifier, AsyncValue<CartResponse?>>(
  (ref) => CartNotifier(ref),
);

final cartItemCountProvider = Provider<int>((ref) {
  final cart = ref.watch(cartProvider);
  return cart.maybeWhen(
    data: (value) => value?.totals.itemsCount ?? 0,
    orElse: () => 0,
  );
});
