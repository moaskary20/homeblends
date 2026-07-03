import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/price_formatter.dart';
import '../../shared/models/loyalty.dart';
import '../../shared/widgets/auth_login_prompt.dart';
import '../../shared/widgets/state_views.dart';
import '../auth/auth_provider.dart';

class PointsScreen extends ConsumerStatefulWidget {
  const PointsScreen({super.key});

  @override
  ConsumerState<PointsScreen> createState() => _PointsScreenState();
}

class _PointsScreenState extends ConsumerState<PointsScreen> {
  LoyaltyProgram? _program;
  List<LoyaltyTransaction> _transactions = [];
  bool _loading = true;
  bool _redeeming = false;
  String? _error;
  final _pointsController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _pointsController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = await ref.read(loyaltyRepositoryProvider.future);
      final results = await Future.wait([
        repo.getProgram(),
        repo.getHistory(),
      ]);
      if (!mounted) return;
      final program = results[0] as LoyaltyProgram;
      setState(() {
        _program = program;
        _transactions = results[1] as List<LoyaltyTransaction>;
        _loading = false;
        if (_pointsController.text.isEmpty && program.canRedeemToWallet) {
          _pointsController.text = '${program.minRedeemPoints}';
        }
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  num get _redeemPreview {
    final program = _program;
    if (program == null) return 0;
    final points = int.tryParse(_pointsController.text) ?? 0;
    return points * program.pointValue;
  }

  Future<void> _redeemToWallet() async {
    final program = _program;
    if (program == null) return;

    final points = int.tryParse(_pointsController.text);
    if (points == null || points <= 0) return;

    setState(() => _redeeming = true);
    try {
      final repo = await ref.read(loyaltyRepositoryProvider.future);
      final updated = await repo.redeemToWallet(points);
      if (!mounted) return;
      setState(() {
        _program = updated;
        _redeeming = false;
      });
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'تم التحويل — أُضيف ${formatPrice(points * program.pointValue)} إلى المحفظة',
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _redeeming = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAuth = ref.watch(isAuthenticatedProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('نقاط الولاء')),
      body: !isAuth
          ? const AuthLoginPrompt(
              redirect: '/points',
              message: 'سجّل الدخول لعرض النقاط',
            )
          : _loading
              ? const LoadingView(message: 'جاري التحميل...')
              : _error != null
                  ? ErrorView(message: _error!, onRetry: _load)
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: _InfoCard(
                                  icon: Icons.stars,
                                  color: AppColors.primary,
                                  label: 'رصيد النقاط',
                                  value: '${_program!.points}',
                                  meta:
                                      'قيمة تقريبية: ${formatPrice(_program!.pointsCashValue)}',
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _InfoCard(
                                  icon: Icons.account_balance_wallet_outlined,
                                  color: AppColors.primaryDark,
                                  label: 'رصيد المحفظة',
                                  value: formatPrice(_program!.storeCredit),
                                  meta: 'من صفحة المحفظة',
                                  onTap: () => context.push('/wallet'),
                                ),
                              ),
                            ],
                          ),
                          if (_program!.vipLevel != null) ...[
                            const SizedBox(height: 12),
                            Card(
                              child: ListTile(
                                leading: const Icon(Icons.workspace_premium,
                                    color: AppColors.primary),
                                title: Text(
                                  'مستوى VIP: ${_program!.vipLevel!.name}',
                                ),
                                subtitle: _program!.vipDiscountPercent > 0
                                    ? Text(
                                        'خصم إضافي ${_program!.vipDiscountPercent}% على الطلبات',
                                      )
                                    : null,
                              ),
                            ),
                          ],
                          const SizedBox(height: 16),
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'تحويل النقاط إلى رصيد المحفظة',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  const Text(
                                    'حوّل نقاطك إلى رصيد نقدي يُستخدم عند الدفع',
                                    style: TextStyle(color: AppColors.muted),
                                  ),
                                  if (_program!.canRedeemToWallet) ...[
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _pointsController,
                                      keyboardType: TextInputType.number,
                                      decoration: InputDecoration(
                                        labelText: 'عدد النقاط',
                                        helperText:
                                            'الحد الأدنى: ${_program!.minRedeemPoints} — المتاح: ${_program!.maxWalletRedeemPoints}',
                                      ),
                                      onChanged: (_) => setState(() {}),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      'ستحصل على: ${formatPrice(_redeemPreview)}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                        color: AppColors.primary,
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    Row(
                                      children: [
                                        TextButton(
                                          onPressed: () {
                                            _pointsController.text =
                                                '${_program!.maxWalletRedeemPoints}';
                                            setState(() {});
                                          },
                                          child: const Text('كل النقاط'),
                                        ),
                                        const Spacer(),
                                        ElevatedButton(
                                          onPressed:
                                              _redeeming ? null : _redeemToWallet,
                                          child: _redeeming
                                              ? const SizedBox(
                                                  width: 20,
                                                  height: 20,
                                                  child: CircularProgressIndicator(
                                                    strokeWidth: 2,
                                                  ),
                                                )
                                              : const Text('تحويل إلى المحفظة'),
                                        ),
                                      ],
                                    ),
                                  ] else
                                    Padding(
                                      padding: const EdgeInsets.only(top: 12),
                                      child: Text(
                                        'تحتاج ${_program!.minRedeemPoints} نقطة على الأقل للتحويل',
                                        style: const TextStyle(
                                          color: AppColors.muted,
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'كيف تكسب النقاط؟',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  _EarnRow(
                                    icon: Icons.shopping_bag_outlined,
                                    text: _program!.earnRateLabel,
                                  ),
                                  _EarnRow(
                                    icon: Icons.savings_outlined,
                                    text: 'كل نقطة = خصم بقيمتها عند الطلب',
                                  ),
                                  if (_program!.expiryMonths > 0)
                                    _EarnRow(
                                      icon: Icons.schedule,
                                      text:
                                          'تنتهي النقاط المكتسبة بعد ${_program!.expiryMonths} شهراً',
                                    ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),
                          const Text(
                            'سجل الحركات',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          if (_transactions.isEmpty)
                            const EmptyView(
                              message: 'لا توجد حركات نقاط بعد',
                              icon: Icons.history,
                            )
                          else
                            ..._transactions.map(_TransactionTile.new),
                        ],
                      ),
                    ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.icon,
    required this.color,
    required this.label,
    required this.value,
    required this.meta,
    this.onTap,
  });

  final IconData icon;
  final Color color;
  final String label;
  final String value;
  final String meta;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: color),
              const SizedBox(height: 8),
              Text(label, style: const TextStyle(color: AppColors.muted)),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 4),
              Text(meta, style: const TextStyle(fontSize: 11, color: AppColors.muted)),
            ],
          ),
        ),
      ),
    );
  }
}

class _EarnRow extends StatelessWidget {
  const _EarnRow({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 10),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile(this.tx);

  final LoyaltyTransaction tx;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppColors.background,
          child: Icon(
            _iconForType(tx.type),
            color: AppColors.primary,
            size: 20,
          ),
        ),
        title: Text(tx.typeLabel),
        subtitle: Text(
          [
            if (tx.description != null) tx.description!,
            if (tx.createdAt != null) _formatDate(tx.createdAt!),
          ].join('\n'),
        ),
        trailing: Text(
          '${tx.isCredit ? '+' : ''}${tx.points}',
          style: TextStyle(
            color: tx.isCredit ? Colors.green : AppColors.error,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  IconData _iconForType(String type) {
    return switch (type) {
      'earn' => Icons.add,
      'redeem' => Icons.redeem,
      'wallet' => Icons.account_balance_wallet,
      'expire' => Icons.timer_off,
      'adjust' => Icons.tune,
      _ => Icons.circle,
    };
  }
}

String _formatDate(String iso) {
  final dt = DateTime.tryParse(iso);
  if (dt == null) return iso;
  return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
}
