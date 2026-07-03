import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/providers/repositories.dart';
import '../../shared/models/category.dart';
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
      final catalog = await ref.read(catalogRepositoryProvider.future);
      final categories = await catalog.getCategories();
      if (!mounted) return;
      setState(() {
        _categories = categories;
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
      appBar: AppBar(title: const Text('التصنيفات')),
      body: _loading
          ? const LoadingView()
          : _error != null
              ? ErrorView(message: _error!, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _categories.length,
                    itemBuilder: (context, index) {
                      final category = _categories[index];
                      return _CategoryTile(category: category);
                    },
                  ),
                ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({required this.category});

  final Category category;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        leading: category.image != null
            ? CircleAvatar(backgroundImage: NetworkImage(category.image!))
            : CircleAvatar(child: Text(category.name[0])),
        title: Text(category.name),
        onExpansionChanged: (expanded) {
          if (!expanded && category.children.isEmpty) {
            context.push('/categories/${category.slug}');
          }
        },
        children: [
          if (category.children.isEmpty)
            ListTile(
              title: const Text('عرض المنتجات'),
              trailing: const Icon(Icons.arrow_back_ios, size: 16),
              onTap: () => context.push('/categories/${category.slug}'),
            )
          else
            ...category.children.map(
              (child) => ListTile(
                title: Text(child.name),
                trailing: const Icon(Icons.arrow_back_ios, size: 16),
                onTap: () => context.push('/categories/${child.slug}'),
              ),
            ),
        ],
      ),
    );
  }
}
