import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/auth/session_manager.dart';
import 'core/config/env.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/auth_provider.dart';

class HomeBlendApp extends ConsumerStatefulWidget {
  const HomeBlendApp({super.key});

  @override
  ConsumerState<HomeBlendApp> createState() => _HomeBlendAppState();
}

class _HomeBlendAppState extends ConsumerState<HomeBlendApp> {
  @override
  void initState() {
    super.initState();
    SessionManager.onUnauthorized = () async {
      ref.read(authProvider.notifier).clearSession();
    };
  }

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'هوم بلند',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      locale: Locale(Env.appLocale),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      routerConfig: router,
    );
  }
}
