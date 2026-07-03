import 'product.dart';

class CartItem {
  const CartItem({
    required this.id,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
    this.isBundle = false,
    this.product,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) => CartItem(
        id: json['id'] as int,
        quantity: json['quantity'] as int,
        unitPrice: _toNum(json['unit_price']) ?? 0,
        subtotal: _toNum(json['subtotal']) ?? 0,
        isBundle: json['is_bundle'] as bool? ?? false,
        product: json['product'] != null
            ? Product.fromJson(json['product'] as Map<String, dynamic>)
            : null,
      );

  final int id;
  final int quantity;
  final num unitPrice;
  final num subtotal;
  final bool isBundle;
  final Product? product;
}

class Cart {
  const Cart({
    required this.id,
    required this.items,
    this.couponCode,
  });

  factory Cart.fromJson(Map<String, dynamic> json) => Cart(
        id: json['id'] as int,
        items: (json['items'] as List<dynamic>? ?? [])
            .map((e) => CartItem.fromJson(e as Map<String, dynamic>))
            .toList(),
        couponCode: json['coupon_code'] as String?,
      );

  final int id;
  final List<CartItem> items;
  final String? couponCode;
}

class CartTotals {
  const CartTotals({required this.subtotal, required this.itemsCount});

  factory CartTotals.fromJson(Map<String, dynamic> json) => CartTotals(
        subtotal: _toNum(json['subtotal']) ?? 0,
        itemsCount: json['items_count'] as int? ?? 0,
      );

  final num subtotal;
  final int itemsCount;
}

class CartResponse {
  const CartResponse({required this.cart, required this.totals});

  factory CartResponse.fromJson(Map<String, dynamic> json) => CartResponse(
        cart: Cart.fromJson(json['cart'] as Map<String, dynamic>),
        totals: CartTotals.fromJson(json['totals'] as Map<String, dynamic>),
      );

  final Cart cart;
  final CartTotals totals;
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
