import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_exception.dart';
import '../../core/providers/repositories.dart';
import '../../core/theme/app_colors.dart';
import '../../shared/models/category.dart';
import '../../shared/widgets/category_grid_card.dart';
import '../../shared/widgets/server_settings_sheet.dart';
import '../../shared/widgets/state_views.dart';

class CategoriesScreen extends ConsumerStatefulWidget {
  const CategoriesScreen({super.key});

  @override
  ConsumerState<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends ConsumerState<CategoriesScreen> {
  List<Category> _categories = [];
  bool _loading = true;
  String? _error;
  bool _connectionError = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _connectionError = false;
    });
    try {
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final categories = await catalog.getCategories();
      if (!mounted) return;
      setState(() {
        _categories = categories;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      final message = e is ApiException ? e.message : e.toString();
      setState(() {
        _error = message;
        _connectionError = _isConnectionError(e);
        _loading = false;
      });
    }
  }

  bool _isConnectionError(Object error) {
    if (error is! ApiException) return false;
    return error.message.contains('تعذر الاتصال') ||
        error.message.contains('انتهت مهلة');
  }

  void _openServerSettings() {
    showServerSettingsSheet(context, ref, onSaved: _load);
  }

  void _openCategory(Category category) {
    context.push('/categories/${category.slug}');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('التصنيفات'),
      ),
      body: _loading
          ? const LoadingView(message: 'جاري تحميل التصنيفات...')
          : _error != null
              ? ErrorView(
                  message: _error!,
                  onRetry: _load,
                  onSettings:
                      _connectionError ? _openServerSettings : null,
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _categories.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: const [
                            SizedBox(height: 120),
                            EmptyView(
                              message: 'لا توجد تصنيفات',
                              icon: Icons.category_outlined,
                            ),
                          ],
                        )
                      : CustomScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          slivers: [
                            SliverToBoxAdapter(
                              child: Padding(
                                padding: const EdgeInsets.fromLTRB(
                                  20,
                                  8,
                                  20,
                                  20,
                                ),
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    Text(
                                      'تسوّق حسب القسم',
                                      style: Theme.of(context)
                                          .textTheme
                                          .headlineSmall
                                          ?.copyWith(
                                            fontWeight: FontWeight.bold,
                                            color: AppColors.text,
                                          ),
                                    ),
                                    const SizedBox(height: 6),
                                    const Text(
                                      'اختر التصنيف المناسب واستكشف منتجاتنا',
                                      style: TextStyle(
                                        color: AppColors.muted,
                                        fontSize: 14,
                                        height: 1.4,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            SliverPadding(
                              padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                              sliver: SliverGrid(
                                gridDelegate:
                                    const SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: 2,
                                  mainAxisSpacing: 16,
                                  crossAxisSpacing: 14,
                                  childAspectRatio: 0.78,
                                ),
                                delegate: SliverChildBuilderDelegate(
                                  (context, index) {
                                    final category = _categories[index];
                                    return CategoryGridCard(
                                      category: category,
                                      onTap: () => _openCategory(category),
                                    );
                                  },
                                  childCount: _categories.length,
                                ),
                              ),
                            ),
                          ],
                        ),
                ),
    );
  }
}
