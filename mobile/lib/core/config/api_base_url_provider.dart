import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/api_client.dart';
import '../providers/repositories.dart';
import 'api_base_url.dart';
import 'env.dart';

Future<void> resetApiBaseUrl(WidgetRef ref) async {
  await ApiBaseUrl.clear();
  ref.read(apiBaseUrlProvider.notifier).state = Env.defaultApiBaseUrl;
  ref.invalidate(apiClientProvider);
  ref.invalidate(authRepositoryProvider);
  ref.invalidate(catalogRepositoryProvider);
  ref.invalidate(homeRepositoryProvider);
  ref.invalidate(cartRepositoryProvider);
  ref.invalidate(checkoutRepositoryProvider);
  ref.invalidate(orderRepositoryProvider);
  ref.invalidate(wishlistRepositoryProvider);
  ref.invalidate(loyaltyRepositoryProvider);
}

Future<void> updateApiBaseUrl(WidgetRef ref, String url) async {
  await ApiBaseUrl.save(url);
  ref.read(apiBaseUrlProvider.notifier).state = ApiBaseUrl.current;
  ref.invalidate(apiClientProvider);
  ref.invalidate(authRepositoryProvider);
  ref.invalidate(catalogRepositoryProvider);
  ref.invalidate(homeRepositoryProvider);
  ref.invalidate(cartRepositoryProvider);
  ref.invalidate(checkoutRepositoryProvider);
  ref.invalidate(orderRepositoryProvider);
  ref.invalidate(wishlistRepositoryProvider);
  ref.invalidate(loyaltyRepositoryProvider);
}
