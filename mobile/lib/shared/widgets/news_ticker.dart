import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

class NewsTicker extends StatefulWidget {
  const NewsTicker({super.key, required this.items});

  final List<String> items;

  @override
  State<NewsTicker> createState() => _NewsTickerState();
}

class _NewsTickerState extends State<NewsTicker> {
  final _controller = ScrollController();
  Timer? _timer;

  List<String> get _items => widget.items;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _startAutoScroll());
  }

  @override
  void didUpdateWidget(covariant NewsTicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.items != widget.items) {
      _timer?.cancel();
      WidgetsBinding.instance.addPostFrameCallback((_) => _startAutoScroll());
    }
  }

  void _startAutoScroll() {
    _timer?.cancel();
    if (!mounted || _items.isEmpty) return;

    _timer = Timer.periodic(const Duration(milliseconds: 30), (_) {
      if (!_controller.hasClients || !mounted) return;

      final position = _controller.position;
      final half = position.maxScrollExtent / 2;
      if (half <= 0) return;

      final next = _controller.offset + 0.9;
      if (next >= half) {
        _controller.jumpTo(next - half);
      } else {
        _controller.jumpTo(next);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_items.isEmpty) return const SizedBox.shrink();

    final loopItems = [..._items, ..._items];

    return Semantics(
      label: 'آخر الأخبار',
      child: ColoredBox(
        color: AppColors.primaryDark,
        child: SizedBox(
          height: 38,
          child: ListView.separated(
            controller: _controller,
            scrollDirection: Axis.horizontal,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 8),
            itemCount: loopItems.length,
            separatorBuilder: (_, __) => const SizedBox(width: 8),
            itemBuilder: (_, index) => _TickerItem(text: loopItems[index]),
          ),
        ),
      ),
    );
  }
}

class _TickerItem extends StatelessWidget {
  const _TickerItem({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Text(
          '●',
          style: TextStyle(
            color: Colors.white70,
            fontSize: 8,
          ),
        ),
        const SizedBox(width: 10),
        Text(
          text,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
