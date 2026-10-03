import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  String? _token;
  Map<String, dynamic>? _user;
  bool _isLoading = false;

  String? get token => _token;
  Map<String, dynamic>? get user => _user;
  bool get isAuthenticated => _token != null && _token!.isNotEmpty;
  bool get isLoading => _isLoading;

  String get userName => _user?['name'] ?? 'Guest Customer';
  String get userPhone => _user?['phone'] ?? '';
  String get userAddress => _user?['address'] ?? '';

  AuthProvider() {
    loadSavedSession();
  }

  Future<void> loadSavedSession() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    final userJson = prefs.getString('user_data');
    if (userJson != null) {
      _user = jsonDecode(userJson);
    }
    notifyListeners();
  }

  Future<Map<String, dynamic>> login(String phone, String password) async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.login(phone, password);
    _isLoading = false;

    if (res['success'] == true && res['data'] != null) {
      _token = res['data']['token'];
      _user = res['data']['user'];

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', _token!);
      await prefs.setString('user_data', jsonEncode(_user));
      notifyListeners();
    }
    notifyListeners();
    return res;
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String phone,
    required String address,
    required String password,
  }) async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.register(
      name: name,
      phone: phone,
      address: address,
      password: password,
    );
    _isLoading = false;

    if (res['success'] == true && res['data'] != null) {
      _token = res['data']['token'];
      _user = res['data']['user'];

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', _token!);
      await prefs.setString('user_data', jsonEncode(_user));
      notifyListeners();
    }
    notifyListeners();
    return res;
  }

  Future<void> logout() async {
    _token = null;
    _user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_data');
    notifyListeners();
  }
}
