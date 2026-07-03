import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/account/account_screen.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/register_screen.dart';
import '../../features/cart/cart_screen.dart';
import '../../features/catalog/categories_screen.dart';
import '../../features/catalog/product_detail_screen.dart';
import '../../features/catalog/search_screen.dart';
import '../../features/checkout/checkout_screen.dart';
import '../../features/home/home_screen.dart';
import '../../features/orders/orders_screen.dart';
import '../../features/cart/cart_provider.dart';
import '../../features/loyalty/points_screen.dart';
import '../../features/splash/splash_screen.dart';
import '../../features/wallet/wallet_screen.dart';
import '../../shared/widgets/custom_bottom_nav_bar.dart';

class MainShell extends ConsumerStatefulWidget {
  const MainShell({
    super.key,
    required this.child,
    required this.location,
  });

  final Widget child;
  final String location;

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(cartProvider.notifier).load());
  }

  void _onTabSelected(int index) {
    switch (index) {
      case 0:
        context.go('/');
      case 1:
        context.go('/categories-tab');
      case 2:
        context.go('/cart-tab');
      case 3:
        context.go('/account');
    }
  }

  @override
  Widget build(BuildContext context) {
    final cartCount = ref.watch(cartItemCountProvider);
    final selectedIndex = bottomNavIndexForPath(widget.location);
    final hideNav = shouldHideBottomNav(widget.location);
    final bottomInset = hideNav ? 0.0 : 80.0;

    return Scaffold(
      extendBody: !hideNav,
      body: MediaQuery(
        data: MediaQuery.of(context).copyWith(
          padding: MediaQuery.of(context).padding.copyWith(
            bottom: MediaQuery.of(context).padding.bottom + bottomInset,
          ),
        ),
        child: widget.child,
      ),
      bottomNavigationBar: hideNav
          ? null
          : CustomBottomNavBar(
              selectedIndex: selectedIndex,
              onSelected: _onTabSelected,
              items: [
                const NavBarItem(
                  icon: Icons.home_outlined,
                  activeIcon: Icons.home_rounded,
                  label: 'الرئيسية',
                ),
                const NavBarItem(
                  icon: Icons.grid_view_outlined,
                  activeIcon: Icons.grid_view_rounded,
                  label: 'التصنيفات',
                ),
                NavBarItem(
                  icon: Icons.shopping_bag_outlined,
                  activeIcon: Icons.shopping_bag_rounded,
                  label: 'السلة',
                  badge: cartCount > 0 ? cartCount : null,
                ),
                const NavBarItem(
                  icon: Icons.person_outline_rounded,
                  activeIcon: Icons.person_rounded,
                  label: 'حسابي',
                ),
              ],
            ),
    );
  }
}

final _rootNavigatorKey = GlobalKey<NavigatorState>();
final _shellNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'shell');

final routerProvider = Provider<GoRouter>((ref) {
  final router = GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: '/splash',
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, __) => const SplashScreen(),
      ),
      ShellRoute(
        navigatorKey: _shellNavigatorKey,
        builder: (context, state, child) => MainShell(
          location: state.uri.path,
          child: child,
        ),
        routes: [
          GoRoute(
            path: '/',
            builder: (_, __) => const HomeScreen(),
          ),
          GoRoute(
            path: '/categories-tab',
            builder: (_, __) => const CategoriesScreen(),
          ),
          GoRoute(
            path: '/cart-tab',
            builder: (_, __) => const CartScreen(),
          ),
          GoRoute(
            path: '/account',
            builder: (_, __) => const AccountScreen(),
          ),
          GoRoute(
            path: '/search',
            builder: (_, __) => const SearchScreen(),
          ),
          GoRoute(
            path: '/categories/:slug',
            builder: (_, state) => CategoryProductsScreen(
              slug: state.pathParameters['slug']!,
              showAll: state.uri.queryParameters['all'] == '1',
            ),
          ),
          GoRoute(
            path: '/products/:slug',
            builder: (_, state) => ProductDetailScreen(
              slug: state.pathParameters['slug']!,
            ),
          ),
          GoRoute(
            path: '/login',
            builder: (_, state) => LoginScreen(
              redirect: state.uri.queryParameters['redirect'],
            ),
          ),
          GoRoute(
            path: '/register',
            builder: (_, state) => RegisterScreen(
              redirect: state.uri.queryParameters['redirect'],
            ),
          ),
          GoRoute(
            path: '/forgot-password',
            builder: (_, __) => const ForgotPasswordScreen(),
          ),
          GoRoute(
            path: '/checkout',
            builder: (_, __) => const CheckoutScreen(),
          ),
          GoRoute(
            path: '/orders',
            builder: (_, __) => const OrdersScreen(),
          ),
          GoRoute(
            path: '/orders/:id',
            builder: (_, state) => OrderDetailScreen(
              orderId: int.parse(state.pathParameters['id']!),
            ),
          ),
          GoRoute(
            path: '/addresses',
            builder: (_, __) => const AddressesScreen(),
          ),
          GoRoute(
            path: '/addresses/new',
            builder: (_, __) => const NewAddressScreen(),
          ),
          GoRoute(
            path: '/wishlist',
            builder: (_, __) => const WishlistScreen(),
          ),
          GoRoute(
            path: '/wallet',
            builder: (_, __) => const WalletScreen(),
          ),
          GoRoute(
            path: '/points',
            builder: (_, __) => const PointsScreen(),
          ),
        ],
      ),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
