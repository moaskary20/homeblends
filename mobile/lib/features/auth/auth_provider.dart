import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../shared/models/user.dart';
import '../../core/providers/repositories.dart';

class AuthNotifier extends StateNotifier<AsyncValue<User?>> {
  AuthNotifier(this._ref) : super(const AsyncValue.loading()) {
    _load();
  }

  final Ref _ref;

  Future<void> _load() async {
    try {
      final repo = await _ref.read(authRepositoryProvider.future);
      final user = await repo.me();
      state = AsyncValue.data(user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => _load();

  Future<void> login(String email, String password) async {
    state = const AsyncValue.loading();
    try {
      final repo = await _ref.read(authRepositoryProvider.future);
      final auth = await repo.login(email: email, password: password);
      state = AsyncValue.data(auth.user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      rethrow;
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    state = const AsyncValue.loading();
    try {
      final repo = await _ref.read(authRepositoryProvider.future);
      final auth = await repo.register(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );
      state = AsyncValue.data(auth.user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      final repo = await _ref.read(authRepositoryProvider.future);
      await repo.logout();
    } catch (_) {
      await _ref.read(tokenStorageProvider).clear();
    }
    state = const AsyncValue.data(null);
  }

  void clearSession() {
    state = const AsyncValue.data(null);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AsyncValue<User?>>(
  (ref) => AuthNotifier(ref),
);

final isAuthenticatedProvider = Provider<bool>((ref) {
  final auth = ref.watch(authProvider);
  return auth.maybeWhen(data: (user) => user != null, orElse: () => false);
});
