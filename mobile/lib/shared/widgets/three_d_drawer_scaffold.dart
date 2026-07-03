import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

class ThreeDDrawerScaffold extends StatefulWidget {
  const ThreeDDrawerScaffold({
    super.key,
    required this.body,
    required this.drawer,
    this.appBar,
    this.backgroundColor,
  });

  final Widget body;
  final Widget drawer;
  final PreferredSizeWidget? appBar;
  final Color? backgroundColor;

  @override
  State<ThreeDDrawerScaffold> createState() => ThreeDDrawerScaffoldState();
}

class ThreeDDrawerScaffoldState extends State<ThreeDDrawerScaffold>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _animation;

  bool _isOpen = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 360),
    );
    _animation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOutCubic,
      reverseCurve: Curves.easeInCubic,
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  bool get isDrawerOpen => _isOpen;

  void openDrawer() {
    if (_isOpen) return;
    setState(() => _isOpen = true);
    _controller.forward();
  }

  void closeDrawer() {
    if (!_isOpen) return;
    _controller.reverse().then((_) {
      if (mounted) setState(() => _isOpen = false);
    });
  }

  void toggleDrawer() {
    if (_isOpen) {
      closeDrawer();
    } else {
      openDrawer();
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    final drawerWidth = math.min(size.width * 0.84, 320.0);
    final isRtl = Directionality.of(context) == TextDirection.rtl;
    final hingeX = isRtl ? 1.0 : 0.0;

    return Material(
      color: widget.backgroundColor ?? AppColors.background,
      child: Stack(
        children: [
          AnimatedBuilder(
            animation: _animation,
            builder: (context, child) {
              final t = _animation.value;
              final shift = drawerWidth * 0.42 * t;
              final angle = 0.14 * t;
              final scale = 1 - (0.06 * t);

              return Transform(
                alignment: Alignment(hingeX * 2 - 1, 0.5),
                transform: Matrix4.identity()
                  ..setEntry(3, 2, 0.0012)
                  ..translate(isRtl ? -shift : shift)
                  ..rotateY(isRtl ? -angle : angle)
                  ..scale(scale),
                child: child,
              );
            },
            child: DecoratedBox(
              decoration: BoxDecoration(
                boxShadow: _isOpen
                    ? [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.22),
                          blurRadius: 24,
                          offset: const Offset(0, 8),
                        ),
                      ]
                    : null,
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(_isOpen ? 18 : 0),
                child: _MainPanel(
                  appBar: widget.appBar,
                  onMenuPressed: toggleDrawer,
                  body: widget.body,
                  backgroundColor: widget.backgroundColor,
                ),
              ),
            ),
          ),
          if (_isOpen)
            Positioned(
              top: 0,
              bottom: 0,
              left: isRtl ? 0 : null,
              right: isRtl ? drawerWidth : null,
              width: isRtl ? null : size.width - drawerWidth,
              child: GestureDetector(
                onTap: closeDrawer,
                child: AnimatedBuilder(
                  animation: _animation,
                  builder: (context, _) {
                    return ColoredBox(
                      color: Colors.black.withValues(
                        alpha: 0.38 * _animation.value,
                      ),
                    );
                  },
                ),
              ),
            ),
          Positioned(
            top: 0,
            bottom: 0,
            right: isRtl ? 0 : null,
            left: isRtl ? null : 0,
            width: drawerWidth,
            child: AnimatedBuilder(
              animation: _animation,
              builder: (context, child) {
                final t = _animation.value;
                final entryAngle = 0.28 * (1 - t);

                return IgnorePointer(
                  ignoring: t < 0.01,
                  child: Transform(
                    alignment: Alignment(hingeX * 2 - 1, 0),
                    transform: Matrix4.identity()
                      ..setEntry(3, 2, 0.0018)
                      ..rotateY(isRtl ? entryAngle : -entryAngle),
                    child: Opacity(
                      opacity: t.clamp(0.0, 1.0),
                      child: child,
                    ),
                  ),
                );
              },
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.28),
                      blurRadius: 28,
                      offset: Offset(isRtl ? -10 : 10, 0),
                    ),
                  ],
                ),
                child: widget.drawer,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MainPanel extends StatelessWidget {
  const _MainPanel({
    required this.appBar,
    required this.onMenuPressed,
    required this.body,
    this.backgroundColor,
  });

  final PreferredSizeWidget? appBar;
  final VoidCallback onMenuPressed;
  final Widget body;
  final Color? backgroundColor;

  @override
  Widget build(BuildContext context) {
    final baseAppBar = appBar;
    PreferredSizeWidget? appBarWithMenu;

    if (baseAppBar is AppBar) {
      appBarWithMenu = AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: onMenuPressed,
        ),
        automaticallyImplyLeading: false,
        title: baseAppBar.title,
        actions: baseAppBar.actions,
        backgroundColor: baseAppBar.backgroundColor,
        foregroundColor: baseAppBar.foregroundColor,
        elevation: baseAppBar.elevation,
        centerTitle: baseAppBar.centerTitle,
      );
    }

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: appBarWithMenu ?? baseAppBar,
      body: body,
    );
  }
}

class DrawerGlassHeader extends StatelessWidget {
  const DrawerGlassHeader({
    super.key,
    required this.title,
    required this.subtitle,
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 8, sigmaY: 8),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(20, 28, 20, 22),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
              colors: [
                Color(0xFFD97706),
                AppColors.primaryDark,
              ],
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.35),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.storefront_rounded,
                  color: Colors.white,
                  size: 28,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  shadows: [
                    Shadow(
                      color: Color(0x40000000),
                      offset: Offset(0, 2),
                      blurRadius: 4,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.88),
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
