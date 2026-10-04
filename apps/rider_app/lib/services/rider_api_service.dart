import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import '../constants/rider_constants.dart';
import '../models/rider_order_model.dart';

class RiderApiService {
  static const String _base = RiderConstants.baseUrl;
  static const String _supabaseUrl = RiderConstants.supabaseUrl;
  static const String _supabaseKey = RiderConstants.supabaseKey;

  static Map<String, String> _headers([String? token]) {
    final map = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      map['Authorization'] = 'Bearer $token';
    }
    return map;
  }

  static Map<String, String> _supabaseHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'apikey': _supabaseKey,
      'Authorization': 'Bearer $_supabaseKey',
    };
  }

  // 1. Fetch Active Orders to Deliver
  static Future<List<RiderOrder>> getActiveDeliveries() async {
    // Attempt 1: Next.js API endpoint
    try {
      final res = await http.get(
        Uri.parse('$_base/rider?action=active'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true && body['data'] is List) {
          return (body['data'] as List).map((o) => RiderOrder.fromJson(o)).toList();
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase fetch
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/orders?select=*,order_items(*)&status=in.(pending,packaging,out_for_delivery)&order=id.desc'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((o) => RiderOrder.fromJson(o)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 2. Fetch Delivered History
  static Future<List<RiderOrder>> getDeliveryHistory() async {
    // Attempt 1: Next.js API
    try {
      final res = await http.get(
        Uri.parse('$_base/rider?action=history'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true && body['data'] is List) {
          return (body['data'] as List).map((o) => RiderOrder.fromJson(o)).toList();
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase fetch
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/orders?select=*,order_items(*)&status=eq.delivered&order=id.desc&limit=40'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((o) => RiderOrder.fromJson(o)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 3. Fetch Summary Stats
  static Future<Map<String, dynamic>> getRiderStats() async {
    // Attempt 1: Next.js API
    try {
      final res = await http.get(
        Uri.parse('$_base/rider?action=stats'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true && body['data'] != null) {
          return body['data'];
        }
      }
    } catch (_) {}

    // Attempt 2: Fallback in-app calculation
    return {
      'active_count': 0,
      'delivered_today_count': 0,
      'cash_collected_today': 0.0,
    };
  }

  // 4. Update Order Delivery Status
  static Future<bool> updateOrderStatus(int orderId, String newStatus) async {
    // Attempt 1: Next.js API
    try {
      final res = await http.post(
        Uri.parse('$_base/rider'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'update_status',
          'order_id': orderId,
          'status': newStatus,
        }),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true) return true;
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase PATCH
    try {
      final res = await http.patch(
        Uri.parse('$_supabaseUrl/orders?id=eq.$orderId'),
        headers: _supabaseHeaders(),
        body: jsonEncode({
          'status': newStatus,
        }),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200 || res.statusCode == 204) {
        return true;
      }
    } catch (_) {}

    return false;
  }

  // 5. Open Google Maps Bike Navigation
  static Future<void> launchNavigation({
    double? latitude,
    double? longitude,
    String? mapUrl,
    required String address,
  }) async {
    Uri? navUri;

    if (latitude != null && longitude != null) {
      // Direct coordinate route in Google Maps
      navUri = Uri.parse('google.navigation:q=$latitude,$longitude&mode=d');
      if (!await canLaunchUrl(navUri)) {
        navUri = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$latitude,$longitude');
      }
    } else if (mapUrl != null && mapUrl.isNotEmpty) {
      navUri = Uri.parse(mapUrl);
    } else {
      // Search address in Tando Adam
      final query = Uri.encodeComponent('$address, Tando Adam');
      navUri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$query');
    }

    if (await canLaunchUrl(navUri)) {
      await launchUrl(navUri, mode: LaunchMode.externalApplication);
    }
  }

  // 6. Direct Phone Call to Customer
  static Future<void> makePhoneCall(String phone) async {
    final clean = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    final uri = Uri.parse('tel:$clean');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  // 7. WhatsApp Customer
  static Future<void> sendWhatsAppMessage({
    required String phone,
    required String orderRef,
    required String customerName,
    required double totalAmount,
  }) async {
    var clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
    if (clean.startsWith('0')) {
      clean = '92${clean.substring(1)}';
    } else if (!clean.startsWith('92') && clean.length == 10) {
      clean = '92$clean';
    }

    final message = 'Assalam o Alaikum $customerName! Main HR Traders Tando Adam ka rider hoon. '
        'Aapka order $orderRef (Total Bill Rs. ${totalAmount.toStringAsFixed(0)}) lekar pohanch raha hoon. '
        'Meharbani farmakar cash tayyar rakhein. Shukriya!';

    final uri = Uri.parse('https://wa.me/$clean?text=${Uri.encodeComponent(message)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }
}
