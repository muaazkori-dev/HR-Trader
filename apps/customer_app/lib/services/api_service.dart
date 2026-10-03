import 'dart:convert';
import 'package:http/http.dart' as http;
import '../constants/app_constants.dart';
import '../models/category_model.dart';
import '../models/product.dart';
import '../models/order_model.dart';

class ApiService {
  static const String _base = AppConstants.baseUrl;

  // Headers helper
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

  // 1. AUTH: Login
  static Future<Map<String, dynamic>> login(String identifier, String password, [String? fcmToken]) async {
    try {
      final res = await http.post(
        Uri.parse('$_base/auth.php'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'login',
          'identifier': identifier,
          'password': password,
          'fcm_token': fcmToken ?? '',
        }),
      );
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Network error. Please check your internet connection.'};
    }
  }

  // 2. AUTH: Register
  static Future<Map<String, dynamic>> register({
    required String name,
    required String phone,
    required String address,
    required String password,
    String? fcmToken,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$_base/auth.php'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'register',
          'name': name,
          'phone': phone,
          'address': address,
          'password': password,
          'fcm_token': fcmToken ?? '',
        }),
      );
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Network error during registration.'};
    }
  }

  // 3. PRODUCTS: Categories
  static Future<List<CategoryModel>> getCategories() async {
    try {
      final res = await http.get(Uri.parse('$_base/products.php?action=categories'), headers: _headers());
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] is List) {
        return (data['data'] as List).map((c) => CategoryModel.fromJson(c)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // 4. PRODUCTS: List with filters
  static Future<Map<String, dynamic>> getProducts({
    String? category,
    String? search,
    String? sort,
    int page = 1,
    int limit = 20,
  }) async {
    try {
      var uri = Uri.parse('$_base/products.php').replace(queryParameters: {
        'action': 'list',
        if (category != null && category != 'all') 'category': category,
        if (search != null && search.isNotEmpty) 'search': search,
        if (sort != null) 'sort': sort,
        'page': page.toString(),
        'limit': limit.toString(),
      });

      final res = await http.get(uri, headers: _headers());
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] != null) {
        var rawList = data['data']['products'] as List? ?? [];
        List<Product> products = rawList.map((p) => Product.fromJson(p)).toList();
        return {
          'success': true,
          'products': products,
          'pagination': data['data']['pagination'] ?? {},
        };
      }
      return {'success': false, 'products': <Product>[]};
    } catch (e) {
      return {'success': false, 'products': <Product>[], 'message': e.toString()};
    }
  }

  // 5. PRODUCTS: Detail
  static Future<Product?> getProductDetail(int productId) async {
    try {
      final res = await http.get(Uri.parse('$_base/products.php?action=detail&id=$productId'), headers: _headers());
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] != null) {
        return Product.fromJson(data['data']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // 6. PRODUCTS: Featured & Banners
  static Future<Map<String, dynamic>> getFeatured() async {
    try {
      final res = await http.get(Uri.parse('$_base/products.php?action=featured'), headers: _headers());
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] != null) {
        var rawProds = data['data']['featured_products'] as List? ?? [];
        var rawBanners = data['data']['banners'] as List? ?? [];
        return {
          'featured_products': rawProds.map((p) => Product.fromJson(p)).toList(),
          'banners': rawBanners,
        };
      }
      return {'featured_products': <Product>[], 'banners': []};
    } catch (e) {
      return {'featured_products': <Product>[], 'banners': []};
    }
  }

  // 7. ORDERS: Place Order
  static Future<Map<String, dynamic>> createOrder({
    required String customerName,
    required String customerPhone,
    required String customerAddress,
    double? latitude,
    double? longitude,
    String? deliveryNotes,
    required List<Map<String, dynamic>> items,
    String? token,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$_base/orders.php'),
        headers: _headers(token),
        body: jsonEncode({
          'action': 'create',
          'customer_name': customerName,
          'customer_phone': customerPhone,
          'customer_address': customerAddress,
          if (latitude != null) 'latitude': latitude,
          if (longitude != null) 'longitude': longitude,
          if (deliveryNotes != null && deliveryNotes.isNotEmpty) 'delivery_notes': deliveryNotes,
          'payment_method': 'COD',
          'items': items,
        }),
      );
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Order placement failed. Check network connection.'};
    }
  }

  // 8. ORDERS: List
  static Future<List<OrderSummary>> getOrders({String? token, String? phone}) async {
    try {
      var uri = Uri.parse('$_base/orders.php').replace(queryParameters: {
        'action': 'list',
        if (phone != null && phone.isNotEmpty) 'phone': phone,
      });

      final res = await http.get(uri, headers: _headers(token));
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] is List) {
        return (data['data'] as List).map((o) => OrderSummary.fromJson(o)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // 9. ORDERS: Live Tracking
  static Future<OrderDetail?> trackOrder(int orderId) async {
    try {
      final res = await http.get(Uri.parse('$_base/orders.php?action=track&id=$orderId'), headers: _headers());
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['data'] != null) {
        return OrderDetail.fromJson(data['data']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }
}
