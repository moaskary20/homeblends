import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_exception.dart';
import '../../core/providers/repositories.dart';
import '../../shared/models/category.dart';
import '../../shared/models/home.dart';
import '../../shared/widgets/app_drawer.dart';
import '../../shared/widgets/customer_reviews_carousel.dart';
import '../../shared/widgets/three_d_drawer_scaffold.dart';
import '../../shared/widgets/hero_slider.dart';
import '../../shared/widgets/news_ticker.dart';
import '../../shared/widgets/product_carousel.dart';
import '../../shared/widgets/server_settings_sheet.dart';
import '../../shared/widgets/state_views.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  final _drawerKey = GlobalKey<ThreeDDrawerScaffoldState>();
  HomeContent? _home;
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
      final homeRepo = await ref.read(homeRepositoryProvider.future);
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final results = await Future.wait([
        homeRepo.getHome(),
        catalog.getCategories(),
      ]);
      if (!mounted) return;
      setState(() {
        _home = results[0] as HomeContent;
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
    return ThreeDDrawerScaffold(
      key: _drawerKey,
      drawer: AppDrawer(
        onClose: () => _drawerKey.currentState?.closeDrawer(),
      ),
      appBar: AppBar(
        title: const Text('هوم بلند'),
        actions: [
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
                    children: [
                      if (_home!.newsTicker.isNotEmpty)
                        NewsTicker(items: _home!.newsTicker),
                      if (_home!.heroSlides.isNotEmpty) ...[
                        HeroSlider(slides: _home!.heroSlides),
                        const SizedBox(height: 20),
                      ],
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            if (_categories.isNotEmpty) ...[
                              SectionHeader(
                                title: 'التصنيفات',
                                onViewAll: () => context.go('/categories-tab'),
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
                                      onTap: () => context.push(
                                        '/categories/${cat.slug}',
                                      ),
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
                                              style: const TextStyle(
                                                fontSize: 12,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    );
                                  },
                                ),
                              ),
                            ] else
                              const EmptyView(
                                message: 'لا توجد تصنيفات',
                                icon: Icons.category_outlined,
                              ),
                            const SizedBox(height: 24),
                            ProductCarousel(
                              title: 'منتجات مميزة',
                              products: _home!.featured,
                            ),
                            const SizedBox(height: 24),
                            ..._home!.departments.map(
                              (section) => Padding(
                                padding: const EdgeInsets.only(bottom: 24),
                                child: ProductCarousel(
                                  title: section.title,
                                  products: section.products,
                                  categorySlug: section.slug,
                                ),
                              ),
                            ),
                            CustomerReviewsCarousel(
                              title: _home!.customerReviewsTitle,
                              reviews: _home!.customerReviews,
                            ),
                            const SizedBox(height: 24),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}

