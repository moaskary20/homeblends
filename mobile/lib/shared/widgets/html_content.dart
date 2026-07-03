import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../core/theme/app_colors.dart';

class HtmlContent extends StatelessWidget {
  const HtmlContent({super.key, required this.html});

  final String html;

  @override
  Widget build(BuildContext context) {
    if (html.trim().isEmpty) {
      return const SizedBox.shrink();
    }

    return Html(
      data: html,
      style: {
        'body': Style(
          margin: Margins.zero,
          padding: HtmlPaddings.zero,
          fontSize: FontSize(15),
          lineHeight: const LineHeight(1.6),
          color: AppColors.text,
        ),
        'p': Style(margin: Margins.only(bottom: 10)),
        'ul': Style(margin: Margins.only(bottom: 10)),
        'ol': Style(margin: Margins.only(bottom: 10)),
        'img': Style(
          width: Width.auto(),
          height: Height.auto(),
          display: Display.block,
        ),
      },
    );
  }
}
