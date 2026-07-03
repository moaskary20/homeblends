import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../shared/models/category.dart';
import '../../shared/models/product.dart';
import '../../shared/widgets/product_card.dart';
import '../../shared/widgets/state_views.dart';

class CategoryProductsScreen extends ConsumerStatefulWidget {
  const CategoryProductsScreen({
    super.key,
    required this.slug,
    this.showAll = false,
  });

  final String slug;
  final bool showAll;

  @override
  ConsumerState<CategoryProductsScreen> createState() =>
      _CategoryProductsScreenState();
}

class _CategoryProductsScreenState
    extends ConsumerState<CategoryProductsScreen> {
  final List<Product> _products = [];
  int _page = 1;
  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  String? _error;
  String _title = 'المنتجات';
  Category? _category;
  bool _browseAll = false;

  @override
  void initState() {
    super.initState();
    _browseAll = widget.showAll;
    _load(reset: true);
  }

  bool get _shouldShowSubcategoryLanding =>
      !_browseAll && (_category?.children.isNotEmpty ?? false);

  Future<void> _load({bool reset = false}) async {
    if (reset) {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
        _hasMore = true;
        _products.clear();
      });
    } else {
      setState(() => _loadingMore = true);
    }

    try {
      final catalog = await ref.read(catalogRepositoryProvider.future);
      if (reset) {
        final category = await catalog.getCategory(widget.slug);
        _category = category;
        _title = category.name;
      }

      if (_shouldShowSubcategoryLanding) {
        if (!mounted) return;
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
        return;
      }

      final result = await catalog.getProducts(
        page: _page,
        categorySlug: widget.slug,
      );
      if (!mounted) return;
      setState(() {
        _products.addAll(result.data);
        _hasMore = result.hasMore;
        _page++;
        _loading = false;
        _loadingMore = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
        _loadingMore = false;
      });
    }
  }

  void _browseAllProducts() {
    setState(() => _browseAll = true);
    _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_title)),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: () => _load(reset: true))
              : _shouldShowSubcategoryLanding
                  ? _SubcategoryLanding(
                      category: _category!,
                      onBrowseAll: _browseAllProducts,
                    )
                  : _products.isEmpty
                      ? const EmptyView(message: 'لا توجد منتجات')
                      : NotificationListener<ScrollNotification>(
                          onNotification: (notification) {
                            if (notification.metrics.pixels >=
                                    notification.metrics.maxScrollExtent -
                                        200 &&
                                _hasMore &&
                                !_loadingMore) {
                              _load();
                            }
                            return false;
                          },
                          child: RefreshIndicator(
                            onRefresh: () => _load(reset: true),
                            child: GridView.builder(
                              padding: const EdgeInsets.all(16),
                              gridDelegate:
                                  const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                childAspectRatio: 0.72,
                                crossAxisSpacing: 12,
                                mainAxisSpacing: 12,
                              ),
                              itemCount:
                                  _products.length + (_loadingMore ? 1 : 0),
                              itemBuilder: (context, index) {
                                if (index >= _products.length) {
                                  return const Center(
                                    child: CircularProgressIndicator(),
                                  );
                                }
                                return ProductCard(product: _products[index]);
                              },
                            ),
                          ),
                        ),
    );
  }
}

class _SubcategoryLanding extends StatelessWidget {
  const _SubcategoryLanding({
    required this.category,
    required this.onBrowseAll,
  });

  final Category category;
  final VoidCallback onBrowseAll;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(
          'اختر قسماً فرعياً',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 12),
        ...category.children.map(
          (child) => Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              leading: child.image != null
                  ? CircleAvatar(
                      backgroundImage: CachedNetworkImageProvider(child.image!),
                    )
                  : CircleAvatar(child: Text(child.name[0])),
              title: Text(child.name),
              trailing: const Icon(Icons.arrow_back_ios, size: 16),
              onTap: () => context.push('/categories/${child.slug}'),
            ),
          ),
        ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          onPressed: onBrowseAll,
          icon: const Icon(Icons.grid_view_outlined),
          label: Text('عرض كل منتجات ${category.name}'),
        ),
      ],
    );
  }
}

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _controller = TextEditingController();
  List<Product> _results = [];
  bool _loading = false;
  String? _error;

  Future<void> _search() async {
    final query = _controller.text.trim();
    if (query.length < 2) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final results = await catalog.search(query);
      if (!mounted) return;
      setState(() {
        _results = results;
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
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: TextField(
          controller: _controller,
          decoration: const InputDecoration(
            hintText: 'ابحث عن منتج...',
            border: InputBorder.none,
          ),
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _search(),
        ),
        actions: [
          IconButton(icon: const Icon(Icons.search), onPressed: _search),
        ],
      ),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _search)
              : _results.isEmpty
                  ? const EmptyView(
                      message: 'ابحث عن منتج بالاسم',
                      icon: Icons.search,
                    )
                  : GridView.builder(
                      padding: const EdgeInsets.all(16),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 0.72,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                      ),
                      itemCount: _results.length,
                      itemBuilder: (_, i) => ProductCard(product: _results[i]),
                    ),
    );
  }
}
