import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
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

  static const _steps = ['العنوان', 'الشحن', 'الدفع'];

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

  num get _subtotal => ref.read(cartProvider).valueOrNull?.totals.subtotal ?? 0;

  num get _shippingCost => _selectedRate?.rate ?? 0;

  num get _codFee => _selectedGateway?.codFee ?? 0;

  num get _total => _subtotal + _shippingCost + _codFee;

  int get _itemsCount =>
      ref.read(cartProvider).valueOrNull?.totals.itemsCount ?? 0;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final checkout = await ref.read(checkoutRepositoryProvider.future);
      await ref.read(cartProvider.notifier).refresh();
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

  bool _canContinue() {
    return switch (_step) {
      0 => _selectedAddress != null,
      1 => _selectedRate != null,
      2 => _selectedGateway != null,
      _ => false,
    };
  }

  void _goNext() {
    if (!_canContinue()) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى إكمال هذا القسم أولاً')),
      );
      return;
    }
    if (_step < 2) {
      setState(() => _step++);
      return;
    }
    _placeOrder();
  }

  void _goBack() {
    if (_step > 0) setState(() => _step--);
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
      await ref.read(cartProvider.notifier).refresh();
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
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('إتمام الشراء'),
        automaticallyImplyLeading: _step == 0,
        leading: _step > 0
            ? IconButton(
                icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
                onPressed: _goBack,
              )
            : null,
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: _CheckoutProgress(
              currentStep: _step,
              steps: _steps,
            ),
          ),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 280),
              switchInCurve: Curves.easeOutCubic,
              switchOutCurve: Curves.easeInCubic,
              child: KeyedSubtree(
                key: ValueKey(_step),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
                  children: [
                    _CheckoutSummaryCard(
                      itemsCount: _itemsCount,
                      subtotal: _subtotal,
                      shipping: _shippingCost,
                      codFee: _codFee,
                      total: _total,
                    ),
                    const SizedBox(height: 20),
                    switch (_step) {
                      0 => _AddressStep(
                          addresses: _addresses,
                          selected: _selectedAddress,
                          onSelected: (address) =>
                              setState(() => _selectedAddress = address),
                          onAddAddress: () async {
                            await context.push('/addresses/new');
                            if (mounted) _load();
                          },
                        ),
                      1 => _ShippingStep(
                          rates: _shippingRates,
                          selected: _selectedRate,
                          onSelected: (rate) =>
                              setState(() => _selectedRate = rate),
                        ),
                      2 => _PaymentStep(
                          gateways: _gateways,
                          selected: _selectedGateway,
                          notesController: _notesController,
                          onSelected: (gateway) =>
                              setState(() => _selectedGateway = gateway),
                        ),
                      _ => const SizedBox.shrink(),
                    },
                  ],
                ),
              ),
            ),
          ),
          _CheckoutBottomBar(
            total: _total,
            step: _step,
            submitting: _submitting,
            canContinue: _canContinue(),
            onPrimary: _goNext,
          ),
        ],
      ),
    );
  }
}

class _CheckoutProgress extends StatelessWidget {
  const _CheckoutProgress({
    required this.currentStep,
    required this.steps,
  });

  final int currentStep;
  final List<String> steps;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: List.generate(steps.length * 2 - 1, (index) {
          if (index.isOdd) {
            final stepIndex = index ~/ 2;
            final done = currentStep > stepIndex;
            return Expanded(
              child: Container(
                height: 3,
                margin: const EdgeInsets.only(bottom: 22),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(4),
                  gradient: done
                      ? const LinearGradient(
                          colors: [AppColors.primary, AppColors.primaryDark],
                        )
                      : null,
                  color: done ? null : AppColors.border,
                ),
              ),
            );
          }

          final stepIndex = index ~/ 2;
          final active = currentStep == stepIndex;
          final done = currentStep > stepIndex;

          return _StepDot(
            label: steps[stepIndex],
            index: stepIndex + 1,
            active: active,
            done: done,
          );
        }),
      ),
    );
  }
}

class _StepDot extends StatelessWidget {
  const _StepDot({
    required this.label,
    required this.index,
    required this.active,
    required this.done,
  });

  final String label;
  final int index;
  final bool active;
  final bool done;

  @override
  Widget build(BuildContext context) {
    final color = active || done ? AppColors.primary : AppColors.muted;

    return Column(
      children: [
        AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          width: active ? 40 : 34,
          height: active ? 40 : 34,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: active || done
                ? const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFFD97706), AppColors.primaryDark],
                  )
                : null,
            color: active || done ? null : AppColors.background,
            border: Border.all(
              color: active || done ? Colors.transparent : AppColors.border,
              width: 1.5,
            ),
            boxShadow: active
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.35),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: done
                ? const Icon(Icons.check_rounded, color: Colors.white, size: 18)
                : Text(
                    '$index',
                    style: TextStyle(
                      color: active ? Colors.white : AppColors.muted,
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: active ? FontWeight.w700 : FontWeight.w500,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _CheckoutSummaryCard extends StatelessWidget {
  const _CheckoutSummaryCard({
    required this.itemsCount,
    required this.subtotal,
    required this.shipping,
    required this.codFee,
    required this.total,
  });

  final int itemsCount;
  final num subtotal;
  final num shipping;
  final num codFee;
  final num total;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFFFFF7ED), Colors.white],
        ),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.08),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.receipt_long_rounded,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'ملخص الطلب',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        '$itemsCount منتج',
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  formatPrice(total),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryDark,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const Divider(height: 1, color: AppColors.border),
            const SizedBox(height: 12),
            _SummaryRow(label: 'المجموع الفرعي', value: formatPrice(subtotal)),
            const SizedBox(height: 6),
            _SummaryRow(label: 'الشحن', value: formatPrice(shipping)),
            if (codFee > 0) ...[
              const SizedBox(height: 6),
              _SummaryRow(
                label: 'رسوم الدفع عند الاستلام',
                value: formatPrice(codFee),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _AddressStep extends StatelessWidget {
  const _AddressStep({
    required this.addresses,
    required this.selected,
    required this.onSelected,
    required this.onAddAddress,
  });

  final List<Address> addresses;
  final Address? selected;
  final ValueChanged<Address> onSelected;
  final VoidCallback onAddAddress;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _SectionTitle(
          icon: Icons.location_on_outlined,
          title: 'عنوان الشحن',
          subtitle: 'اختر عنوان التوصيل',
        ),
        const SizedBox(height: 12),
        if (addresses.isEmpty)
          _EmptyStepCard(
            icon: Icons.add_location_alt_outlined,
            message: 'لا يوجد عنوان محفوظ',
            actionLabel: 'إضافة عنوان جديد',
            onAction: onAddAddress,
          )
        else ...[
          ...addresses.map(
            (address) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _SelectableCard(
                selected: selected?.id == address.id,
                onTap: () => onSelected(address),
                leading: const Icon(Icons.home_work_outlined),
                title: address.displayName,
                subtitle: '${address.addressLine1}\n${address.city}',
                trailing: address.isDefault
                    ? const _BadgeChip(label: 'افتراضي')
                    : null,
              ),
            ),
          ),
          OutlinedButton.icon(
            onPressed: onAddAddress,
            icon: const Icon(Icons.add_rounded),
            label: const Text('إضافة عنوان آخر'),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
          ),
        ],
      ],
    );
  }
}

class _ShippingStep extends StatelessWidget {
  const _ShippingStep({
    required this.rates,
    required this.selected,
    required this.onSelected,
  });

  final List<ShippingRate> rates;
  final ShippingRate? selected;
  final ValueChanged<ShippingRate> onSelected;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _SectionTitle(
          icon: Icons.local_shipping_outlined,
          title: 'طريقة الشحن',
          subtitle: 'اختر سرعة التوصيل المناسبة',
        ),
        const SizedBox(height: 12),
        if (rates.isEmpty)
          const _EmptyStepCard(
            icon: Icons.local_shipping_outlined,
            message: 'لا توجد خيارات شحن متاحة حالياً',
          )
        else
          ...rates.map(
            (rate) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _SelectableCard(
                selected: selected?.id == rate.id,
                onTap: () => onSelected(rate),
                leading: const Icon(Icons.inventory_2_outlined),
                title: rate.name,
                subtitle: rate.estimatedDays != null
                    ? 'التوصيل خلال ${rate.estimatedDays} يوم'
                    : rate.zone,
                trailing: Text(
                  formatPrice(rate.rate),
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryDark,
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _PaymentStep extends StatelessWidget {
  const _PaymentStep({
    required this.gateways,
    required this.selected,
    required this.notesController,
    required this.onSelected,
  });

  final List<PaymentGateway> gateways;
  final PaymentGateway? selected;
  final TextEditingController notesController;
  final ValueChanged<PaymentGateway> onSelected;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _SectionTitle(
          icon: Icons.payments_outlined,
          title: 'طريقة الدفع',
          subtitle: 'اختر وسيلة الدفع المناسبة',
        ),
        const SizedBox(height: 12),
        ...gateways.map(
          (gateway) => Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: _SelectableCard(
              selected: selected?.code == gateway.code,
              onTap: () => onSelected(gateway),
              leading: Icon(_gatewayIcon(gateway.code)),
              title: gateway.name,
              subtitle: gateway.instructions ?? gateway.description,
              trailing: gateway.codFee > 0
                  ? Text(
                      '+ ${formatPrice(gateway.codFee)}',
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.muted,
                      ),
                    )
                  : null,
            ),
          ),
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
          ),
          child: TextField(
            controller: notesController,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'ملاحظات على الطلب (اختياري)',
              hintText: 'مثال: اتصل قبل التوصيل',
              border: InputBorder.none,
              contentPadding: EdgeInsets.all(16),
            ),
          ),
        ),
      ],
    );
  }

  IconData _gatewayIcon(String code) {
    return switch (code) {
      'cod' => Icons.payments_outlined,
      'paypal' => Icons.account_balance_wallet_outlined,
      'stripe' => Icons.credit_card_rounded,
      _ => Icons.payment_rounded,
    };
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: AppColors.primary, size: 22),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                subtitle,
                style: const TextStyle(color: AppColors.muted, fontSize: 13),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _SelectableCard extends StatelessWidget {
  const _SelectableCard({
    required this.selected,
    required this.onTap,
    required this.leading,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  final bool selected;
  final VoidCallback onTap;
  final Widget leading;
  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.primary.withValues(alpha: 0.06)
                : AppColors.surface,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? AppColors.primary : AppColors.border,
              width: selected ? 2 : 1,
            ),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.12),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ]
                : [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: selected
                      ? AppColors.primary.withValues(alpha: 0.15)
                      : AppColors.background,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: IconTheme(
                  data: IconThemeData(
                    color: selected ? AppColors.primaryDark : AppColors.muted,
                    size: 22,
                  ),
                  child: leading,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: selected ? AppColors.primaryDark : AppColors.text,
                      ),
                    ),
                    if (subtitle != null && subtitle!.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        subtitle!,
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontSize: 13,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              if (trailing != null) ...[
                const SizedBox(width: 8),
                trailing!,
              ],
              const SizedBox(width: 8),
              AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                width: 22,
                height: 22,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: selected ? AppColors.primary : Colors.transparent,
                  border: Border.all(
                    color: selected ? AppColors.primary : AppColors.border,
                    width: 2,
                  ),
                ),
                child: selected
                    ? const Icon(Icons.check, size: 14, color: Colors.white)
                    : null,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyStepCard extends StatelessWidget {
  const _EmptyStepCard({
    required this.icon,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Icon(icon, size: 48, color: AppColors.muted.withValues(alpha: 0.6)),
          const SizedBox(height: 12),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.muted),
          ),
          if (actionLabel != null && onAction != null) ...[
            const SizedBox(height: 16),
            ElevatedButton(onPressed: onAction, child: Text(actionLabel!)),
          ],
        ],
      ),
    );
  }
}

class _BadgeChip extends StatelessWidget {
  const _BadgeChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: AppColors.primaryDark,
        ),
      ),
    );
  }
}

class _CheckoutBottomBar extends StatelessWidget {
  const _CheckoutBottomBar({
    required this.total,
    required this.step,
    required this.submitting,
    required this.canContinue,
    required this.onPrimary,
  });

  final num total;
  final int step;
  final bool submitting;
  final bool canContinue;
  final VoidCallback onPrimary;

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom;

    return Container(
      padding: EdgeInsets.fromLTRB(16, 14, 16, 14 + bottom),
      decoration: BoxDecoration(
        color: AppColors.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, -6),
          ),
        ],
        border: const Border(top: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'الإجمالي',
                  style: TextStyle(color: AppColors.muted, fontSize: 12),
                ),
                Text(
                  formatPrice(total),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryDark,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 2,
            child: ElevatedButton(
              onPressed: submitting || !canContinue ? null : onPrimary,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: canContinue ? 4 : 0,
                shadowColor: AppColors.primary.withValues(alpha: 0.4),
              ),
              child: submitting
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : Text(
                      step == 2 ? 'تأكيد الطلب' : 'متابعة',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
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
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      contentPadding: EdgeInsets.zero,
      content: SizedBox(
        width: MediaQuery.of(context).size.width * 0.9,
        height: MediaQuery.of(context).size.height * 0.7,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: WebViewWidget(controller: _controller),
        ),
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
