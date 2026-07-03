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

class WalletScreen extends ConsumerStatefulWidget {
  const WalletScreen({super.key});

  @override
  ConsumerState<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends ConsumerState<WalletScreen> {
  LoyaltyProgram? _program;
  List<LoyaltyTransaction> _transactions = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
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
      final history = results[1] as List<LoyaltyTransaction>;
      setState(() {
        _program = program;
        _transactions =
            history.where((tx) => tx.type == 'wallet').toList();
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
    final isAuth = ref.watch(isAuthenticatedProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('المحفظة')),
      body: !isAuth
          ? const AuthLoginPrompt(
              redirect: '/wallet',
              message: 'سجّل الدخول لعرض المحفظة',
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
                          _BalanceCard(
                            icon: Icons.account_balance_wallet,
                            label: 'رصيد المحفظة',
                            value: formatPrice(_program!.storeCredit),
                            subtitle:
                                'يُستخدم كخصم تلقائي عند إتمام الطلبات القادمة',
                          ),
                          const SizedBox(height: 16),
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: const [
                                  Text(
                                    'كيف تستخدم المحفظة؟',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                    ),
                                  ),
                                  SizedBox(height: 8),
                                  Text(
                                    '• يُخصم الرصيد من إجمالي الطلب عند الدفع.\n'
                                    '• يمكنك تحويل نقاط الولاء إلى رصيد من صفحة النقاط.\n'
                                    '• الرصيد لا ينتهي صلاحيته.',
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'حركات المحفظة',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              TextButton(
                                onPressed: () => context.push('/points'),
                                child: const Text('تحويل نقاط'),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          if (_transactions.isEmpty)
                            const EmptyView(
                              message: 'لا توجد حركات محفظة بعد',
                              icon: Icons.receipt_long_outlined,
                            )
                          else
                            ..._transactions.map(_TransactionTile.new),
                        ],
                      ),
                    ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.subtitle,
  });

  final IconData icon;
  final String label;
  final String value;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: AppColors.primary,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          children: [
            Icon(icon, color: Colors.white, size: 40),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(color: Colors.white70),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ),
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
          child: const Icon(Icons.swap_horiz, color: AppColors.primary),
        ),
        title: Text(tx.typeLabel),
        subtitle: Text(
          [
            if (tx.description != null) tx.description!,
            if (tx.createdAt != null) _formatDate(tx.createdAt!),
          ].join('\n'),
        ),
        trailing: Text(
          '${tx.points}',
          style: TextStyle(
            color: tx.isCredit ? Colors.green : AppColors.error,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}

String _formatDate(String iso) {
  final dt = DateTime.tryParse(iso);
  if (dt == null) return iso;
  return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
}
