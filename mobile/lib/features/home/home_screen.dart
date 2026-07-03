import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_exception.dart';
import '../../core/providers/repositories.dart';
import '../../shared/models/category.dart';
import '../../shared/models/product.dart';
import '../../shared/widgets/product_card.dart';
import '../../shared/widgets/server_settings_sheet.dart';
import '../../shared/widgets/state_views.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  List<Product> _featured = [];
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
      final results = await Future.wait([
        catalog.getFeatured(),
        catalog.getCategories(),
      ]);
      if (!mounted) return;
      setState(() {
        _featured = results[0] as List<Product>;
        _categories = results[1] as List<Category>;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('هوم بلند'),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_ethernet),
            tooltip: 'إعدادات الاتصال',
            onPressed: _openServerSettings,
          ),
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () => context.push('/search'),
          ),
        ],
      ),
      body: _loading
          ? const LoadingView(message: 'جاري التحميل...')
          : _error != null
              ? ErrorView(
                  message: _error!,
                  onRetry: _load,
                  onSettings:
                      _connectionError ? _openServerSettings : null,
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (_categories.isNotEmpty) ...[
                        const Text(
                          'التصنيفات',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        SizedBox(
                          height: 100,
                          child: ListView.separated(
                            scrollDirection: Axis.horizontal,
                            itemCount: _categories.length,
                            separatorBuilder: (_, __) =>
                                const SizedBox(width: 12),
                            itemBuilder: (context, index) {
                              final cat = _categories[index];
                              return InkWell(
                                onTap: () =>
                                    context.push('/categories/${cat.slug}'),
                                child: Column(
                                  children: [
                                    CircleAvatar(
                                      radius: 32,
                                      backgroundImage: cat.image != null
                                          ? NetworkImage(cat.image!)
                                          : null,
                                      child: cat.image == null
                                          ? Text(cat.name[0])
                                          : null,
                                    ),
                                    const SizedBox(height: 6),
                                    SizedBox(
                                      width: 72,
                                      child: Text(
                                        cat.name,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(fontSize: 12),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 24),
                      ] else
                        const EmptyView(
                          message: 'لا توجد تصنيفات',
                          icon: Icons.category_outlined,
                        ),
                      const SizedBox(height: 24),
                      const Text(
                        'منتجات مميزة',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (_featured.isEmpty)
                        const EmptyView(message: 'لا توجد منتجات مميزة')
                      else
                        GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          gridDelegate:
                              const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 0.72,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                          itemCount: _featured.length,
                          itemBuilder: (_, i) =>
                              ProductCard(product: _featured[i]),
                        ),
                    ],
                  ),
                ),
    );
  }
}
