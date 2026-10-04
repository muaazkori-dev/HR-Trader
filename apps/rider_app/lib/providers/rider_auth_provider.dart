import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class RiderAuthProvider with ChangeNotifier {
  bool _isAuthenticated = false;
  String _riderName = 'Muhammad Usman';
  String _riderPhone = '03033943814';
  String _bikeNumber = 'ADM-1428';
  bool _isOnline = true;

  bool get isAuthenticated => _isAuthenticated;
  String get riderName => _riderName;
  String get riderPhone => _riderPhone;
  String get bikeNumber => _bikeNumber;
  bool get isOnline => _isOnline;

  RiderAuthProvider() {
    _loadFromPrefs();
  }

  Future<void> _loadFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    _isAuthenticated = prefs.getBool('rider_is_logged_in') ?? false;
    _riderName = prefs.getString('rider_name') ?? 'Muhammad Usman';
    _riderPhone = prefs.getString('rider_phone') ?? '03033943814';
    _bikeNumber = prefs.getString('rider_bike') ?? 'ADM-1428';
    _isOnline = prefs.getBool('rider_is_online') ?? true;
    notifyListeners();
  }

  Future<void> login({
    required String name,
    required String phone,
    String bike = 'ADM-1428',
  }) async {
    _isAuthenticated = true;
    _riderName = name.trim();
    _riderPhone = phone.trim();
    _bikeNumber = bike.trim();
    _isOnline = true;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('rider_is_logged_in', true);
    await prefs.setString('rider_name', _riderName);
    await prefs.setString('rider_phone', _riderPhone);
    await prefs.setString('rider_bike', _bikeNumber);
    await prefs.setBool('rider_is_online', true);

    notifyListeners();
  }

  Future<void> toggleDuty() async {
    _isOnline = !_isOnline;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('rider_is_online', _isOnline);
    notifyListeners();
  }

  Future<void> logout() async {
    _isAuthenticated = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('rider_is_logged_in', false);
    notifyListeners();
  }
}
