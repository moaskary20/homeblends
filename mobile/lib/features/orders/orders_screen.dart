import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/models/order.dart';
import '../../shared/widgets/state_views.dart';

class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  List<Order> _orders = [];
  bool _loading = true;
  String? _error;

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
      final repo = await ref.read(orderRepositoryProvider.future);
      final orders = await repo.getOrders();
      if (!mounted) return;
      setState(() {
        _orders = orders;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('طلباتي')),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : _orders.isEmpty
                  ? const EmptyView(message: 'لا توجد طلبات')
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _orders.length,
                        itemBuilder: (context, index) {
                          final order = _orders[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              title: Text(order.orderNumber),
                              subtitle: Text(order.statusLabel),
                              trailing: Text(
                                formatPrice(order.total),
                                style: const TextStyle(
                                  color: AppColors.primary,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              onTap: () =>
                                  context.push('/orders/${order.id}'),
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}

class OrderDetailScreen extends ConsumerStatefulWidget {
  const OrderDetailScreen({super.key, required this.orderId});

  final int orderId;

  @override
  ConsumerState<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends ConsumerState<OrderDetailScreen> {
  Order? _order;
  OrderTracking? _tracking;
  bool _loading = true;
  String? _error;

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
      final repo = await ref.read(orderRepositoryProvider.future);
      final order = await repo.getOrder(widget.orderId);
      OrderTracking? tracking;
      try {
        tracking = await repo.getTracking(widget.orderId);
      } catch (_) {}
      if (!mounted) return;
      setState(() {
        _order = order;
        _tracking = tracking;
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

    final order = _order!;
    final tracking = _tracking;

    return Scaffold(
      appBar: AppBar(title: Text(order.orderNumber)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    order.statusLabel,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                  if (order.trackingNumber != null)
                    Text('رقم التتبع: ${order.trackingNumber}'),
                  const Divider(),
                  _totalRow('المجموع الفرعي', order.subtotal),
                  _totalRow('الخصم', order.discountAmount),
                  _totalRow('الشحن', order.shippingAmount),
                  _totalRow('الضريبة', order.taxAmount),
                  const Divider(),
                  _totalRow('الإجمالي', order.total, bold: true),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'المنتجات',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          ...order.items.map(
            (item) => Card(
              child: ListTile(
                title: Text(item.productName),
                subtitle: Text('الكمية: ${item.quantity}'),
                trailing: Text(formatPrice(item.total)),
              ),
            ),
          ),
          if (tracking != null && tracking.history.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text(
              'تتبع الطلب',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            ...tracking.history.map(
              (h) => ListTile(
                leading: const Icon(Icons.local_shipping_outlined),
                title: Text(h.statusLabel),
                subtitle: h.comment != null ? Text(h.comment!) : null,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _totalRow(String label, num value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontWeight: bold ? FontWeight.bold : null)),
          Text(
            formatPrice(value),
            style: TextStyle(
              fontWeight: bold ? FontWeight.bold : null,
              color: bold ? AppColors.primary : null,
            ),
          ),
        ],
      ),
    );
  }
}
