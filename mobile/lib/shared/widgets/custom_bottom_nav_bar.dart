import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

class NavBarItem {
  const NavBarItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    this.badge,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
  final int? badge;
}

class CustomBottomNavBar extends StatelessWidget {
  const CustomBottomNavBar({
    super.key,
    required this.selectedIndex,
    required this.onSelected,
    required this.items,
  });

  final int selectedIndex;
  final ValueChanged<int> onSelected;
  final List<NavBarItem> items;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        16,
        0,
        16,
        12 + MediaQuery.paddingOf(context).bottom,
      ),
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: AppColors.primaryDark.withValues(alpha: 0.18),
              blurRadius: 24,
              offset: const Offset(0, 10),
              spreadRadius: -4,
            ),
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: Container(
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0xFFFFFFFF),
                  Color(0xFFF3EDE4),
                ],
              ),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.9),
                width: 1.5,
              ),
              borderRadius: BorderRadius.circular(28),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
            child: Row(
              children: List.generate(items.length, (index) {
                return Expanded(
                  child: _NavBarButton(
                    item: items[index],
                    selected: selectedIndex == index,
                    onTap: () => onSelected(index),
                  ),
                );
              }),
            ),
          ),
        ),
      ),
    );
  }
}

class _NavBarButton extends StatefulWidget {
  const _NavBarButton({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final NavBarItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  State<_NavBarButton> createState() => _NavBarButtonState();
}

class _NavBarButtonState extends State<_NavBarButton> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final icon = widget.selected ? widget.item.activeIcon : widget.item.icon;
    final iconColor =
        widget.selected ? Colors.white : AppColors.muted.withValues(alpha: 0.9);

    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) => setState(() => _pressed = false),
      onTapCancel: () => setState(() => _pressed = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _pressed ? 0.94 : 1,
        duration: const Duration(milliseconds: 120),
        curve: Curves.easeOut,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 320),
          curve: Curves.easeOutCubic,
          margin: const EdgeInsets.symmetric(horizontal: 2),
          padding: EdgeInsets.symmetric(
            horizontal: widget.selected ? 14 : 0,
            vertical: 10,
          ),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: widget.selected
                ? const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      Color(0xFFD97706),
                      AppColors.primaryDark,
                    ],
                  )
                : null,
            boxShadow: widget.selected
                ? [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.45),
                      blurRadius: 12,
                      offset: const Offset(0, 5),
                    ),
                    BoxShadow(
                      color: Colors.white.withValues(alpha: 0.25),
                      blurRadius: 0,
                      offset: const Offset(0, -1),
                    ),
                  ]
                : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            textDirection: TextDirection.rtl,
            mainAxisSize: MainAxisSize.min,
            children: [
              _NavIcon(
                icon: icon,
                color: iconColor,
                badge: widget.item.badge,
              ),
              AnimatedSize(
                duration: const Duration(milliseconds: 320),
                curve: Curves.easeOutCubic,
                alignment: Alignment.centerRight,
                child: widget.selected
                    ? Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: Text(
                          widget.item.label,
                          maxLines: 1,
                          overflow: TextOverflow.fade,
                          softWrap: false,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                            shadows: [
                              Shadow(
                                color: Color(0x40000000),
                                offset: Offset(0, 1),
                                blurRadius: 2,
                              ),
                            ],
                          ),
                        ),
                      )
                    : const SizedBox.shrink(),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavIcon extends StatelessWidget {
  const _NavIcon({
    required this.icon,
    required this.color,
    this.badge,
  });

  final IconData icon;
  final Color color;
  final int? badge;

  @override
  Widget build(BuildContext context) {
    final child = Icon(icon, color: color, size: 24);

    if (badge == null || badge! <= 0) return child;

    return Badge(
      isLabelVisible: true,
      label: Text('$badge'),
      backgroundColor: AppColors.error,
      textColor: Colors.white,
      child: child,
    );
  }
}

int bottomNavIndexForPath(String path) {
  if (path.startsWith('/categories') || path == '/categories-tab') {
    return 1;
  }
  if (path.startsWith('/cart') || path == '/checkout') {
    return 2;
  }
  if (path == '/account' ||
      path.startsWith('/orders') ||
      path.startsWith('/addresses') ||
      path == '/wishlist' ||
      path == '/wallet' ||
      path == '/points') {
    return 3;
  }
  return 0;
}

bool shouldHideBottomNav(String path) {
  return path == '/splash' ||
      path == '/login' ||
      path == '/register' ||
      path == '/forgot-password';
}
