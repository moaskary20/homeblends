import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/config/api_base_url.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await ApiBaseUrl.loadSaved();
  runApp(const ProviderScope(child: HomeBlendApp()));
}
