import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import '../constants/admin_constants.dart';
import '../models/admin_models.dart';

class AdminApiService {
  static const String _base = AdminConstants.baseUrl;
  static const String _supabaseUrl = AdminConstants.supabaseUrl;
  static const String _supabaseKey = AdminConstants.supabaseKey;

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

  // 1. Dashboard Metrics
  static Future<AdminDashboardStats> getDashboardStats() async {
    // Attempt 1: Next.js API
    try {
      final res = await http.get(
        Uri.parse('$_base/owner?action=dashboard'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body['success'] == true && body['data'] != null) {
          return AdminDashboardStats.fromJson(body['data']);
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase fetch
    try {
      final todayStart = DateTime.now().subtract(const Duration(hours: 24)).toIso8601String();
      final ordersRes = await http.get(
        Uri.parse('$_supabaseUrl/orders?select=total_amount,status&created_at=gte.$todayStart'),
        headers: _supabaseHeaders(),
      );

      final prodsRes = await http.get(
        Uri.parse('$_supabaseUrl/products?select=stock_quantity'),
        headers: _supabaseHeaders(),
      );

      double rev = 0;
      int ordersCount = 0;
      int deliveredCount = 0;
      int pendingCount = 0;

      if (ordersRes.statusCode == 200) {
        final oList = jsonDecode(ordersRes.body) as List;
        ordersCount = oList.length;
        for (var o in oList) {
          if (o['status'] != 'cancelled') {
            rev += double.tryParse(o['total_amount'].toString()) ?? 0;
          }
          if (o['status'] == 'delivered') deliveredCount++;
          if (o['status'] == 'pending' || o['status'] == 'packaging' || o['status'] == 'out_for_delivery') {
            pendingCount++;
          }
        }
      }

      int lowStock = 0;
      int totalProds = 0;
      if (prodsRes.statusCode == 200) {
        final pList = jsonDecode(prodsRes.body) as List;
        totalProds = pList.length;
        for (var p in pList) {
          final s = int.tryParse(p['stock_quantity'].toString()) ?? 0;
          if (s <= 5) lowStock++;
        }
      }

      return AdminDashboardStats(
        todayRevenue: rev,
        todayOrdersCount: ordersCount,
        deliveredTodayCount: deliveredCount,
        pendingOrdersCount: pendingCount,
        lowStockCount: lowStock,
        totalProductsCount: totalProds,
      );
    } catch (_) {}

    return AdminDashboardStats(
      todayRevenue: 0,
      todayOrdersCount: 0,
      deliveredTodayCount: 0,
      pendingOrdersCount: 0,
      lowStockCount: 0,
      totalProductsCount: 0,
    );
  }

  // 2. Fetch Orders
  static Future<List<AdminOrder>> getOrders({String? status}) async {
    try {
      var url = '$_supabaseUrl/orders?select=*,order_items(*)&order=id.desc&limit=60';
      if (status != null && status.isNotEmpty && status != 'all') {
        url += '&status=eq.$status';
      }

      final res = await http.get(Uri.parse(url), headers: _supabaseHeaders()).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((o) => AdminOrder.fromJson(o)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 3. Update Order Status
  static Future<bool> updateOrderStatus(int orderId, String newStatus) async {
    try {
      final res = await http.patch(
        Uri.parse('$_supabaseUrl/orders?id=eq.$orderId'),
        headers: _supabaseHeaders(),
        body: jsonEncode({'status': newStatus}),
      ).timeout(const Duration(seconds: 8));

      return res.statusCode == 200 || res.statusCode == 204;
    } catch (_) {
      return false;
    }
  }

  // 4. Products List
  static Future<List<AdminProduct>> getProducts({String? search, String? category}) async {
    try {
      var url = '$_supabaseUrl/products?select=*&order=id.desc&limit=150';
      if (category != null && category.isNotEmpty && category != 'all') {
        url += '&category=eq.$category';
      }
      if (search != null && search.isNotEmpty) {
        url += '&name=ilike.*$search*';
      }

      final res = await http.get(Uri.parse(url), headers: _supabaseHeaders()).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((p) => AdminProduct.fromJson(p)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 5. Quick Stock Toggle
  static Future<bool> toggleStock(int productId, bool inStock) async {
    final newStock = inStock ? 15 : 0;
    try {
      final res = await http.patch(
        Uri.parse('$_supabaseUrl/products?id=eq.$productId'),
        headers: _supabaseHeaders(),
        body: jsonEncode({'stock_quantity': newStock}),
      );
      return res.statusCode == 200 || res.statusCode == 204;
    } catch (_) {
      return false;
    }
  }

  // 6. Quick Price & Stock Update
  static Future<bool> updateProductQuick({
    required int productId,
    required double price,
    required int stockQuantity,
    required double purchasePrice,
  }) async {
    try {
      final res = await http.patch(
        Uri.parse('$_supabaseUrl/products?id=eq.$productId'),
        headers: _supabaseHeaders(),
        body: jsonEncode({
          'price': price,
          'stock_quantity': stockQuantity,
          'purchase_price': purchasePrice,
        }),
      );
      return res.statusCode == 200 || res.statusCode == 204;
    } catch (_) {
      return false;
    }
  }

  // 7. Add Product
  static Future<bool> addProduct(Map<String, dynamic> data) async {
    // Attempt 1: Next.js API
    try {
      final res = await http.post(
        Uri.parse('$_base/owner'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'add_product',
          ...data,
        }),
      );
      if (res.statusCode == 200) return true;
    } catch (_) {}

    // Attempt 2: Direct Supabase
    try {
      final res = await http.post(
        Uri.parse('$_supabaseUrl/products'),
        headers: _supabaseHeaders(),
        body: jsonEncode(data),
      );
      return res.statusCode == 201 || res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // 8. Settings Map
  static Future<Map<String, String>> getSettings() async {
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/settings?select=*'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        final map = <String, String>{};
        for (var s in list) {
          map[s['key_name']?.toString() ?? ''] = s['val_value']?.toString() ?? '';
        }
        return map;
      }
    } catch (_) {}

    return {};
  }

  // 9. Update Setting Value
  static Future<bool> updateSetting(String keyName, String valValue) async {
    try {
      final res = await http.post(
        Uri.parse('$_supabaseUrl/settings'),
        headers: {
          ..._supabaseHeaders(),
          'Prefer': 'resolution=merge-duplicates',
        },
        body: jsonEncode({
          'key_name': keyName,
          'val_value': valValue,
        }),
      );
      return res.statusCode == 200 || res.statusCode == 201;
    } catch (_) {
      return false;
    }
  }

  // 10. Process POS Counter Sale
  static Future<bool> processPosSale({
    String customerName = 'Walk-in Counter Customer',
    required List<Map<String, dynamic>> items,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$_base/owner'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'pos_sale',
          'customer_name': customerName,
          'items': items,
        }),
      );

      if (res.statusCode == 200) return true;
    } catch (_) {}

    return false;
  }

  // 11. Customer Demands List
  static Future<List<AdminDemand>> getDemands() async {
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/demands?select=*&order=created_at.desc&limit=50'),
        headers: _supabaseHeaders(),
      );
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((d) => AdminDemand.fromJson(d)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 12. Quick Communication & Navigation Launchers
  static Future<void> callCustomer(String phone) async {
    final clean = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    final uri = Uri.parse('tel:$clean');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  static Future<void> sendWhatsApp({
    required String phone,
    required String message,
  }) async {
    var clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
    if (clean.startsWith('0')) {
      clean = '92${clean.substring(1)}';
    } else if (!clean.startsWith('92') && clean.length == 10) {
      clean = '92$clean';
    }
    final uri = Uri.parse('https://wa.me/$clean?text=${Uri.encodeComponent(message)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  static Future<void> openMap(String? mapUrl, String address) async {
    Uri uri;
    if (mapUrl != null && mapUrl.isNotEmpty) {
      uri = Uri.parse(mapUrl);
    } else {
      uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent('$address, Tando Adam')}');
    }
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }
}
