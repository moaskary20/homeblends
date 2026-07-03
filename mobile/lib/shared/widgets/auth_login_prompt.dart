import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';

class AuthLoginPrompt extends StatelessWidget {
  const AuthLoginPrompt({super.key, required this.redirect, this.message});

  final String redirect;
  final String? message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.lock_outline, size: 48, color: AppColors.muted),
          const SizedBox(height: 12),
          Text(message ?? 'سجّل الدخول للمتابعة'),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: () => context.push('/login?redirect=$redirect'),
            child: const Text('تسجيل الدخول'),
          ),
        ],
      ),
    );
  }
}
