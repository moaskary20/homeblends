import 'package:flutter/material.dart';

import '../../core/config/env.dart';

class AppLogo extends StatelessWidget {
  const AppLogo({
    super.key,
    this.height = 36,
  });

  static const assetPath = 'src/logo.png';

  final double height;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: Env.appName,
      image: true,
      child: Image.asset(
        assetPath,
        height: height,
        fit: BoxFit.contain,
        filterQuality: FilterQuality.high,
        errorBuilder: (_, __, ___) => Text(
          Env.appName,
          style: Theme.of(context).appBarTheme.titleTextStyle,
        ),
      ),
    );
  }
}
