import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../core/providers/repositories.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/models/address.dart';
import '../../shared/models/shipping.dart';
import '../../shared/widgets/state_views.dart';
import '../cart/cart_provider.dart';

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  int _step = 0;
  List<Address> _addresses = [];
  List<ShippingRate> _shippingRates = [];
  List<PaymentGateway> _gateways = [];
  Address? _selectedAddress;
  ShippingRate? _selectedRate;
  PaymentGateway? _selectedGateway;
  final _notesController = TextEditingController();
  bool _loading = true;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final checkout = await ref.read(checkoutRepositoryProvider.future);
      final cart = ref.read(cartProvider).valueOrNull;
      final subtotal = cart?.totals.subtotal ?? 0;
      final results = await Future.wait([
        checkout.getAddresses(),
        checkout.getShippingRates(subtotal: subtotal),
        checkout.getPaymentGateways(),
      ]);
      if (!mounted) return;
      final addresses = results[0] as List<Address>;
      setState(() {
        _addresses = addresses;
        _shippingRates = results[1] as List<ShippingRate>;
        _gateways = results[2] as List<PaymentGateway>;
        _selectedAddress = addresses.cast<Address?>().firstWhere(
              (a) => a?.isDefault == true,
              orElse: () => addresses.isNotEmpty ? addresses.first : null,
            );
        _selectedRate =
            _shippingRates.isNotEmpty ? _shippingRates.first : null;
        _selectedGateway = _gateways.isNotEmpty ? _gateways.first : null;
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

  Future<void> _placeOrder() async {
    final address = _selectedAddress;
    final rate = _selectedRate;
    final gateway = _selectedGateway;
    if (address == null || rate == null || gateway == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('أكمل جميع الخطوات')),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      final checkout = await ref.read(checkoutRepositoryProvider.future);
      final cart = ref.read(cartProvider).valueOrNull;
      final order = await checkout.placeOrder(
        shippingAddress: address.toShippingJson(),
        shippingRateId: rate.id,
        paymentGateway: gateway.code,
        couponCode: cart?.cart.couponCode,
        notes: _notesController.text.trim().isEmpty
            ? null
            : _notesController.text.trim(),
      );
      await ref.read(cartProvider.notifier).load();
      if (!mounted) return;

      if (order.paymentAction?.approvalUrl != null) {
        await _handlePayPal(order.paymentAction!.approvalUrl!);
      }

      if (!mounted) return;
      context.go('/orders/${order.id}');
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _handlePayPal(String url) async {
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) => _PayPalWebView(url: url),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: LoadingView(message: 'جاري تحميل الدفع...'));
    }
    if (_error != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('إتمام الشراء')),
        body: ErrorView(message: _error!, onRetry: _load),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('إتمام الشراء')),
      body: Stepper(
        currentStep: _step,
        onStepContinue: () {
          if (_step < 2) {
            setState(() => _step++);
          } else {
            _placeOrder();
          }
        },
        onStepCancel: _step > 0 ? () => setState(() => _step--) : null,
        controlsBuilder: (context, details) {
          return Padding(
            padding: const EdgeInsets.only(top: 16),
            child: Row(
              children: [
                ElevatedButton(
                  onPressed: _submitting ? null : details.onStepContinue,
                  child: _submitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(_step == 2 ? 'تأكيد الطلب' : 'التالي'),
                ),
                if (details.onStepCancel != null) ...[
                  const SizedBox(width: 12),
                  TextButton(
                    onPressed: details.onStepCancel,
                    child: const Text('رجوع'),
                  ),
                ],
              ],
            ),
          );
        },
        steps: [
          Step(
            title: const Text('عنوان الشحن'),
            isActive: _step >= 0,
            content: Column(
              children: [
                if (_addresses.isEmpty)
                  OutlinedButton(
                    onPressed: () => context.push('/addresses/new'),
                    child: const Text('إضافة عنوان'),
                  )
                else
                  ..._addresses.map(
                    (address) => RadioListTile<Address>(
                      value: address,
                      groupValue: _selectedAddress,
                      onChanged: (v) => setState(() => _selectedAddress = v),
                      title: Text(address.displayName),
                      subtitle: Text(
                        '${address.addressLine1}, ${address.city}',
                      ),
                    ),
                  ),
              ],
            ),
          ),
          Step(
            title: const Text('الشحن'),
            isActive: _step >= 1,
            content: Column(
              children: _shippingRates
                  .map(
                    (rate) => RadioListTile<ShippingRate>(
                      value: rate,
                      groupValue: _selectedRate,
                      onChanged: (v) => setState(() => _selectedRate = v),
                      title: Text(rate.name),
                      subtitle: Text(formatPrice(rate.rate)),
                    ),
                  )
                  .toList(),
            ),
          ),
          Step(
            title: const Text('الدفع'),
            isActive: _step >= 2,
            content: Column(
              children: [
                ..._gateways.map(
                  (gateway) => RadioListTile<PaymentGateway>(
                    value: gateway,
                    groupValue: _selectedGateway,
                    onChanged: (v) => setState(() => _selectedGateway = v),
                    title: Text(gateway.name),
                    subtitle: gateway.instructions != null
                        ? Text(gateway.instructions!)
                        : null,
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _notesController,
                  decoration: const InputDecoration(
                    labelText: 'ملاحظات (اختياري)',
                  ),
                  maxLines: 2,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PayPalWebView extends StatefulWidget {
  const _PayPalWebView({required this.url});

  final String url;

  @override
  State<_PayPalWebView> createState() => _PayPalWebViewState();
}

class _PayPalWebViewState extends State<_PayPalWebView> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (url) {
            if (url.contains('payment/paypal')) {
              Navigator.of(context).pop();
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      contentPadding: EdgeInsets.zero,
      content: SizedBox(
        width: MediaQuery.of(context).size.width * 0.9,
        height: MediaQuery.of(context).size.height * 0.7,
        child: WebViewWidget(controller: _controller),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('إغلاق'),
        ),
        TextButton(
          onPressed: () => launchUrl(Uri.parse(widget.url)),
          child: const Text('فتح في المتصفح'),
        ),
      ],
    );
  }
}
