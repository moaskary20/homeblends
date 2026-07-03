import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/storage/session_storage.dart';
import '../../core/storage/token_storage.dart';
import '../../core/utils/json_helpers.dart';
import '../../core/utils/media_url.dart';
import '../../shared/models/address.dart';
import '../../shared/models/cart.dart';
import '../../shared/models/category.dart';
import '../../shared/models/order.dart';
import '../../shared/models/product.dart';
import '../../shared/models/review.dart';
import '../../shared/models/shipping.dart';
import '../../shared/models/user.dart';

class AuthRepository {
  AuthRepository(this._client, this._tokenStorage, this._sessionStorage);

  final ApiClient _client;
  final TokenStorage _tokenStorage;
  final SessionStorage _sessionStorage;

  Future<AuthResponse> login({
    required String email,
    required String password,
  }) async {
    final sessionId = await _sessionStorage.getOrCreate();
    final response = await _client.post<Map<String, dynamic>>(
      '/auth/login',
      data: {
        'email': email,
        'password': password,
        'session_id': sessionId,
      },
    );
    final auth = AuthResponse.fromJson(response.data!);
    await _tokenStorage.write(auth.token);
    return auth;
  }

  Future<AuthResponse> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    final sessionId = await _sessionStorage.getOrCreate();
    final response = await _client.post<Map<String, dynamic>>(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        if (phone != null) 'phone': phone,
        'session_id': sessionId,
      },
    );
    final auth = AuthResponse.fromJson(response.data!);
    await _tokenStorage.write(auth.token);
    return auth;
  }

  Future<void> forgotPassword(String email) async {
    await _client.post('/auth/forgot-password', data: {'email': email});
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout');
    } finally {
      await _tokenStorage.clear();
    }
  }

  Future<User?> me() async {
    final token = await _tokenStorage.read();
    if (token == null || token.isEmpty) return null;
    final response = await _client.get<Map<String, dynamic>>('/auth/me');
    return User.fromJson(response.data!);
  }
}

class CatalogRepository {
  CatalogRepository(this._client);

  final ApiClient _client;

  Future<List<Category>> getCategories() async {
    final response = await _client.get<dynamic>('/categories');
    return parseList(response.data, Category.fromJson);
  }

  Future<Category> getCategory(String slug) async {
    final response = await _client.get<Map<String, dynamic>>('/categories/$slug');
    return parseData(response.data, Category.fromJson);
  }

  Future<PaginatedResponse<Product>> getProducts({
    int page = 1,
    int? categoryId,
    String? categorySlug,
    String? sort,
  }) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/products',
      queryParameters: {
        'page': page,
        if (categorySlug != null) 'category_slug': categorySlug,
        if (categorySlug == null && categoryId != null) 'category_id': categoryId,
        if (sort != null) 'sort': sort,
      },
    );
    return PaginatedResponse.fromJson(response.data!, Product.fromJson);
  }

  Future<List<Product>> getFeatured() async {
    final response = await _client.get<dynamic>('/products/featured');
    return parseList(response.data, Product.fromJson);
  }

  Future<Product> getProduct(String slug) async {
    final response = await _client.get<Map<String, dynamic>>('/products/$slug');
    return parseData(response.data, Product.fromJson);
  }

  Future<List<Product>> search(String query) async {
    final response = await _client.get<dynamic>(
      '/products/search',
      queryParameters: {'q': query},
    );
    return parseList(response.data, Product.fromJson);
  }

  Future<List<Review>> getReviews(String slug) async {
    final response = await _client.get<dynamic>('/products/$slug/reviews');
    return parseList(response.data, Review.fromJson);
  }
}

class CartRepository {
  CartRepository(this._client);

  final ApiClient _client;

  Future<CartResponse> getCart() async {
    final response = await _client.get<Map<String, dynamic>>('/cart');
    return CartResponse.fromJson(response.data!);
  }

  Future<CartResponse> addItem({
    required int productId,
    int? variantId,
    int quantity = 1,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/cart/items',
      data: {
        'product_id': productId,
        if (variantId != null) 'product_variant_id': variantId,
        'quantity': quantity,
      },
    );
    return CartResponse.fromJson(response.data!);
  }

  Future<CartResponse> updateItem(int itemId, int quantity) async {
    final response = await _client.patch<Map<String, dynamic>>(
      '/cart/items/$itemId',
      data: {'quantity': quantity},
    );
    return CartResponse.fromJson(response.data!);
  }

  Future<CartResponse> removeItem(int itemId) async {
    final response = await _client.delete<Map<String, dynamic>>(
      '/cart/items/$itemId',
    );
    return CartResponse.fromJson(response.data!);
  }

  Future<CartResponse> applyCoupon(String code) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/cart/coupon',
      data: {'code': code},
    );
    return CartResponse.fromJson(response.data!);
  }
}

class CheckoutRepository {
  CheckoutRepository(this._client);

  final ApiClient _client;

  Future<List<Address>> getAddresses() async {
    final response = await _client.get<dynamic>('/addresses');
    return parseList(response.data, Address.fromJson);
  }

  Future<Address> createAddress(Map<String, dynamic> data) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/addresses',
      data: data,
    );
    return Address.fromJson(response.data!);
  }

  Future<List<ShippingRate>> getShippingRates({num subtotal = 0}) async {
    final response = await _client.get<dynamic>(
      '/shipping-rates',
      queryParameters: {'country': 'EG', 'subtotal': subtotal},
    );
    return parseList(response.data, ShippingRate.fromJson);
  }

  Future<List<PaymentGateway>> getPaymentGateways() async {
    final response = await _client.get<dynamic>('/payment-gateways');
    return parseList(response.data, PaymentGateway.fromJson);
  }

  Future<Order> placeOrder({
    required Map<String, dynamic> shippingAddress,
    required int shippingRateId,
    required String paymentGateway,
    String? couponCode,
    int loyaltyPoints = 0,
    String? notes,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/checkout',
      data: {
        'shipping_address': shippingAddress,
        'shipping_rate_id': shippingRateId,
        'payment_gateway': paymentGateway,
        if (couponCode != null) 'coupon_code': couponCode,
        'loyalty_points': loyaltyPoints,
        if (notes != null) 'notes': notes,
      },
    );
    final data = response.data!;
    if (data['data'] is Map<String, dynamic>) {
      return Order.fromJson(data['data'] as Map<String, dynamic>);
    }
    return Order.fromJson(data);
  }
}

class OrderRepository {
  OrderRepository(this._client);

  final ApiClient _client;

  Future<List<Order>> getOrders() async {
    final response = await _client.get<dynamic>('/orders');
    return parseList(response.data, Order.fromJson);
  }

  Future<Order> getOrder(int id) async {
    final response = await _client.get<Map<String, dynamic>>('/orders/$id');
    final data = response.data!;
    if (data['data'] is Map<String, dynamic>) {
      return Order.fromJson(data['data'] as Map<String, dynamic>);
    }
    return Order.fromJson(data);
  }

  Future<OrderTracking> getTracking(int id) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/orders/$id/tracking',
    );
    return OrderTracking.fromJson(response.data!);
  }
}

class WishlistRepository {
  WishlistRepository(this._client);

  final ApiClient _client;

  Future<List<Product>> getWishlist() async {
    final response = await _client.get<Map<String, dynamic>>('/wishlist');
    final data = response.data!;
    final items = data['items'] as List<dynamic>? ?? [];
    return items.map((e) {
      final item = e as Map<String, dynamic>;
      return Product(
        id: item['id'] as int,
        name: item['name'] as String,
        slug: item['slug'] as String,
        mainImage: MediaUrl.resolve(item['thumb'] as String?),
        effectivePrice: item['price'] as num?,
      );
    }).toList();
  }

  Future<void> toggle(int productId) async {
    await _client.post('/wishlist/$productId/toggle');
  }
}

final authRepositoryProvider = FutureProvider<AuthRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return AuthRepository(
    client,
    ref.watch(tokenStorageProvider),
    await ref.watch(sessionStorageProvider.future),
  );
});

final catalogRepositoryProvider = FutureProvider<CatalogRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return CatalogRepository(client);
});

final cartRepositoryProvider = FutureProvider<CartRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return CartRepository(client);
});

final checkoutRepositoryProvider = FutureProvider<CheckoutRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return CheckoutRepository(client);
});

final orderRepositoryProvider = FutureProvider<OrderRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return OrderRepository(client);
});

final wishlistRepositoryProvider = FutureProvider<WishlistRepository>((ref) async {
  final client = await ref.watch(apiClientProvider.future);
  return WishlistRepository(client);
});
