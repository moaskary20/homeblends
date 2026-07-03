import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../shared/models/category.dart';
import '../../features/auth/auth_provider.dart';
import 'state_views.dart';
import 'three_d_drawer_scaffold.dart';

class AppDrawer extends ConsumerStatefulWidget {
  const AppDrawer({super.key, this.onClose});

  final VoidCallback? onClose;

  @override
  ConsumerState<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends ConsumerState<AppDrawer> {
  List<Category> _categories = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  void _close() {
    widget.onClose?.call();
  }

  Future<void> _loadCategories() async {
    try {
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final categories = await catalog.getCategories();
      if (!mounted) return;
      setState(() {
        _categories = categories;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _navigateAuth(String path) {
    _close();
    final isAuth = ref.read(isAuthenticatedProvider);
    if (isAuth) {
      context.push(path);
    } else {
      context.push('/login?redirect=$path');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const DrawerGlassHeader(
              title: 'هوم بلند',
              subtitle: 'تسوق حسب القسم',
            ),
            _DrawerActionTile(
              icon: Icons.account_balance_wallet_outlined,
              label: 'المحفظة',
              onTap: () => _navigateAuth('/wallet'),
            ),
            _DrawerActionTile(
              icon: Icons.stars_outlined,
              label: 'النقاط',
              onTap: () => _navigateAuth('/points'),
            ),
            const Divider(height: 1, indent: 16, endIndent: 16),
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 14, 16, 8),
              child: Text(
                'الأقسام',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: AppColors.muted,
                  fontSize: 13,
                ),
              ),
            ),
            Expanded(
              child: _loading
                  ? const LoadingView(message: 'جاري تحميل الأقسام...')
                  : _categories.isEmpty
                      ? const EmptyView(
                          message: 'لا توجد أقسام',
                          icon: Icons.category_outlined,
                        )
                      : ListView(
                          padding: const EdgeInsets.only(bottom: 16),
                          children: _categories
                              .map(
                                (c) => _CategoryDrawerTile(
                                  category: c,
                                  onClose: _close,
                                ),
                              )
                              .toList(),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DrawerActionTile extends StatelessWidget {
  const _DrawerActionTile({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: AppColors.primaryDark, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),
              ),
              const Icon(
                Icons.chevron_left_rounded,
                color: AppColors.muted,
                size: 20,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CategoryDrawerTile extends StatelessWidget {
  const _CategoryDrawerTile({
    required this.category,
    required this.onClose,
    this.depth = 0,
  });

  final Category category;
  final VoidCallback onClose;
  final int depth;

  @override
  Widget build(BuildContext context) {
    final padding = EdgeInsetsDirectional.only(start: 16 + depth * 12.0);

    if (category.children.isEmpty) {
      return ListTile(
        contentPadding: padding,
        dense: true,
        title: Text(category.name),
        trailing: const Icon(Icons.chevron_left_rounded, size: 16),
        onTap: () {
          onClose();
          context.push('/categories/${category.slug}');
        },
      );
    }

    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: ExpansionTile(
        tilePadding: padding,
        childrenPadding: EdgeInsets.zero,
        iconColor: AppColors.primary,
        collapsedIconColor: AppColors.muted,
        title: Text(
          category.name,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        children: [
          ListTile(
            contentPadding: EdgeInsetsDirectional.only(start: 28 + depth * 12.0),
            dense: true,
            title: const Text('عرض كل المنتجات'),
            trailing: const Icon(Icons.chevron_left_rounded, size: 14),
            onTap: () {
              onClose();
              context.push('/categories/${category.slug}?all=1');
            },
          ),
          ...category.children.map(
            (child) => _CategoryDrawerTile(
              category: child,
              onClose: onClose,
              depth: depth + 1,
            ),
          ),
        ],
      ),
    );
  }
}
