import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../shared/api/api_client.dart';
import 'package:dio/dio.dart';

class AuthState {
  final String? token;
  final Map<String, dynamic>? user;
  final bool isLoading;

  const AuthState({
    this.token,
    this.user,
    this.isLoading = false,
  });

  AuthState copyWith({
    String? token,
    Map<String, dynamic>? user,
    bool? isLoading,
  }) {
    return AuthState(
      token: token ?? this.token,
      user: user ?? this.user,
      isLoading: isLoading ?? this.isLoading,
    );
  }

  bool get isAuthenticated => token != null && token!.isNotEmpty;
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(const AuthState()) {
    _loadToken();
  }

  Future<void> _loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('access_token');
    if (token != null) {
      state = state.copyWith(token: token);
    }
  }

  Future<bool> login(String username, String password) async {
    state = state.copyWith(isLoading: true);

    try {
      final response = await ApiClient.dio.post('/auth/login', data: {
        'username': username,
        'password': password,
      });

      final token = response.data['access_token'] as String?;
      final user = response.data['user'] as Map<String, dynamic>?;

      if (token != null) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('access_token', token);

        state = AuthState(
          token: token,
          user: user,
          isLoading: false,
        );
        return true;
      }

      state = state.copyWith(isLoading: false);
      return false;
    } on DioException {
      state = state.copyWith(isLoading: false);
      return false;
    } catch (e) {
      state = state.copyWith(isLoading: false);
      return false;
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('access_token');
    state = const AuthState();
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier();
});
