import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../models/home.dart';

class HeroSlider extends StatefulWidget {
  const HeroSlider({super.key, required this.slides});

  final List<HeroSlide> slides;

  @override
  State<HeroSlider> createState() => _HeroSliderState();
}

class _HeroSliderState extends State<HeroSlider> {
  late final PageController _controller;
  int _index = 0;

  @override
  void initState() {
    super.initState();
    _controller = PageController();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _openSlide(HeroSlide slide) {
    final url = slide.url?.trim();
    if (url == null || url.isEmpty || url == '#') return;

    if (url.startsWith('/')) {
      context.push(url);
      return;
    }

    final uri = Uri.tryParse(url);
    if (uri != null && uri.path.isNotEmpty) {
      context.push(uri.path);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.slides.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        AspectRatio(
          aspectRatio: 16 / 9,
          child: PageView.builder(
            controller: _controller,
            itemCount: widget.slides.length,
            onPageChanged: (index) => setState(() => _index = index),
            itemBuilder: (_, index) {
              final slide = widget.slides[index];
              return GestureDetector(
                onTap: () => _openSlide(slide),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (slide.image != null)
                      CachedNetworkImage(
                        imageUrl: slide.image!,
                        fit: BoxFit.cover,
                        placeholder: (_, __) => const ColoredBox(
                          color: AppColors.background,
                          child: Center(child: CircularProgressIndicator()),
                        ),
                        errorWidget: (_, __, ___) => const ColoredBox(
                          color: AppColors.background,
                          child: Icon(Icons.image_not_supported, size: 48),
                        ),
                      )
                    else
                      const ColoredBox(color: AppColors.background),
                    Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.bottomCenter,
                          end: Alignment.topCenter,
                          colors: [
                            Colors.black.withValues(alpha: 0.65),
                            Colors.transparent,
                          ],
                        ),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.end,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (slide.title.isNotEmpty)
                            Text(
                              slide.title,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          if (slide.subtitle != null &&
                              slide.subtitle!.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text(
                              slide.subtitle!,
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 14,
                              ),
                            ),
                          ],
                          if (slide.cta != null && slide.cta!.isNotEmpty) ...[
                            const SizedBox(height: 12),
                            FilledButton(
                              onPressed: () => _openSlide(slide),
                              style: FilledButton.styleFrom(
                                backgroundColor: Colors.white,
                                foregroundColor: AppColors.primaryDark,
                              ),
                              child: Text(slide.cta!),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
        if (widget.slides.length > 1) ...[
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              widget.slides.length,
              (i) => AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: _index == i ? 20 : 8,
                height: 8,
                decoration: BoxDecoration(
                  color: _index == i ? AppColors.primary : AppColors.border,
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}
