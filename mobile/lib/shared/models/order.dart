class OrderItem {
  const OrderItem({
    required this.productName,
    required this.sku,
    required this.quantity,
    required this.unitPrice,
    required this.total,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) => OrderItem(
        productName: json['product_name'] as String,
        sku: json['sku'] as String,
        quantity: json['quantity'] as int,
        unitPrice: _toNum(json['unit_price']) ?? 0,
        total: _toNum(json['total']) ?? 0,
      );

  final String productName;
  final String sku;
  final int quantity;
  final num unitPrice;
  final num total;
}

class OrderStatusHistory {
  const OrderStatusHistory({
    required this.status,
    required this.statusLabel,
    this.comment,
    this.createdAt,
  });

  factory OrderStatusHistory.fromJson(Map<String, dynamic> json) =>
      OrderStatusHistory(
        status: json['status'] as String,
        statusLabel: json['status_label'] as String,
        comment: json['comment'] as String?,
        createdAt: json['created_at']?.toString(),
      );

  final String status;
  final String statusLabel;
  final String? comment;
  final String? createdAt;
}

class PaymentAction {
  const PaymentAction({
    required this.type,
    this.approvalUrl,
  });

  factory PaymentAction.fromJson(Map<String, dynamic> json) => PaymentAction(
        type: json['type'] as String,
        approvalUrl: json['approval_url'] as String?,
      );

  final String type;
  final String? approvalUrl;
}

class Order {
  const Order({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.statusLabel,
    required this.subtotal,
    required this.total,
    required this.currency,
    this.shippingMethod,
    this.discountAmount = 0,
    this.shippingAmount = 0,
    this.taxAmount = 0,
    this.trackingNumber,
    this.paymentStatus,
    this.createdAt,
    this.items = const [],
    this.history = const [],
    this.paymentAction,
  });

  factory Order.fromJson(Map<String, dynamic> json) => Order(
        id: json['id'] as int,
        orderNumber: json['order_number'] as String,
        status: json['status'] as String,
        statusLabel: json['status_label'] as String,
        shippingMethod: json['shipping_method'] as String?,
        subtotal: _toNum(json['subtotal']) ?? 0,
        discountAmount: _toNum(json['discount_amount']) ?? 0,
        shippingAmount: _toNum(json['shipping_amount']) ?? 0,
        taxAmount: _toNum(json['tax_amount']) ?? 0,
        total: _toNum(json['total']) ?? 0,
        currency: json['currency'] as String? ?? 'EGP',
        trackingNumber: json['tracking_number'] as String?,
        paymentStatus: json['payment_status'] as String?,
        createdAt: json['created_at']?.toString(),
        items: (json['items'] as List<dynamic>? ?? [])
            .map((e) => OrderItem.fromJson(e as Map<String, dynamic>))
            .toList(),
        history: (json['history'] as List<dynamic>? ?? [])
            .map((e) => OrderStatusHistory.fromJson(e as Map<String, dynamic>))
            .toList(),
        paymentAction: json['payment_action'] != null
            ? PaymentAction.fromJson(
                json['payment_action'] as Map<String, dynamic>,
              )
            : null,
      );

  final int id;
  final String orderNumber;
  final String status;
  final String statusLabel;
  final String? shippingMethod;
  final num subtotal;
  final num discountAmount;
  final num shippingAmount;
  final num taxAmount;
  final num total;
  final String currency;
  final String? trackingNumber;
  final String? paymentStatus;
  final String? createdAt;
  final List<OrderItem> items;
  final List<OrderStatusHistory> history;
  final PaymentAction? paymentAction;
}

class OrderTracking {
  const OrderTracking({
    required this.orderNumber,
    required this.status,
    required this.statusLabel,
    this.trackingNumber,
    this.shippingMethod,
    this.history = const [],
  });

  factory OrderTracking.fromJson(Map<String, dynamic> json) => OrderTracking(
        orderNumber: json['order_number'] as String,
        status: json['status'] as String,
        statusLabel: json['status_label'] as String,
        trackingNumber: json['tracking_number'] as String?,
        shippingMethod: json['shipping_method'] as String?,
        history: (json['history'] as List<dynamic>? ?? [])
            .map((e) => OrderStatusHistory.fromJson(e as Map<String, dynamic>))
            .toList(),
      );

  final String orderNumber;
  final String status;
  final String statusLabel;
  final String? trackingNumber;
  final String? shippingMethod;
  final List<OrderStatusHistory> history;
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
