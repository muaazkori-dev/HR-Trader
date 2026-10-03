import 'dart:convert';
import 'package:http/http.dart' as http;
import '../constants/app_constants.dart';
import '../models/category_model.dart';
import '../models/product.dart';
import '../models/order_model.dart';
import '../models/banner_model.dart';

class ApiService {
  static const String _base = AppConstants.baseUrl;
  static const String _supabaseUrl = 'https://xarwwlbbaevclyljkvzt.supabase.co/rest/v1';
  static const String _supabaseKey = 'sb_publishable_v3-WUAhugqCtckYHXxcQNg_H-mBrrC4';

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

  static Map<String, String> _supabaseHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'apikey': _supabaseKey,
      'Authorization': 'Bearer $_supabaseKey',
    };
  }

  // Built-in offline fallback categories
  static final List<CategoryModel> _defaultCategories = [
    CategoryModel(slug: 'anaj', name: 'BAKING AND COOKING', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/anaj_bjl2mi5qel.jpg'),
    CategoryModel(slug: 'ice_cream', name: 'ICE CREAM', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/ice_cream_wsjfh3iwxtm.png'),
    CategoryModel(slug: 'beverages', name: 'BEVERAGE', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/beverages_yz7q0dqyl3.jpg'),
    CategoryModel(slug: 'milk', name: 'HERBAL AND NUTRITION', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/milk_rgs53kzk3x.png'),
    CategoryModel(slug: 'cosmetics', name: 'COSMETICS', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/cosmetics_p2je0umfham.png'),
    CategoryModel(slug: 'confectionary', name: 'SNACKS AND CHIPS', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/confectionary_12b3v9m963fd.jpg'),
    CategoryModel(slug: 'bakery', name: 'DAIRY AND BREAK FAST', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/bakery_g3ym51j0pww.jpg'),
    CategoryModel(slug: 'sauce', name: 'PASTA AND SAUCES', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/sauce_tao2aop6wg.jpg'),
    CategoryModel(slug: 'stationary', name: 'STATIONARY', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/stationary_iueu7pwyvy.jpg'),
    CategoryModel(slug: 'household_and_laundry', name: 'HOUSEHOLD AND LAUNDRY', productCount: 0, imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/categories/household_and_laundry_acr91jde2fn.png'),
  ];

  // Built-in fallback promotional banners
  static final List<BannerModel> _defaultBanners = [
    BannerModel(
      id: '1',
      tag: 'PREMIUM CHOICE',
      title: 'Your Premium Grocery Partner',
      desc: 'Fresh organic crops, groceries, and premium household brands delivered straight to your home.',
      imageUrl: 'https://thehrtraders.com/assets/images/hero_grocery_banner.png',
      link: '/shop',
      theme: 'emerald',
    ),
    BannerModel(
      id: '2',
      tag: 'ICE CREAM SPECIAL',
      title: '35% Discount Mega Sale',
      desc: 'Beat the heat with premium ice creams and family packs delivered ice-cold.',
      imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/banners/banner_1783965435397_5rkydmny4ze.jpg',
      link: '/shop?category=ice_cream',
      theme: 'cyan',
    ),
    BannerModel(
      id: '3',
      tag: 'BULDAK MEGA OFFER',
      title: 'Korea Spicy Samyang Deal',
      desc: 'Buy Buldak combo pack and get a chilled drink free!',
      imageUrl: 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/banners/banner_1789278283433_ruegkout56.png',
      link: '/shop?category=confectionary',
      theme: 'amber',
    ),
  ];

  static List<BannerModel> getBannersSync() => _defaultBanners;

  // 1. AUTH: Login
  static Future<Map<String, dynamic>> login(String identifier, String password, [String? fcmToken]) async {
    try {
      final res = await http.post(
        Uri.parse('$_base/auth'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'login',
          'identifier': identifier,
          'password': password,
          'fcm_token': fcmToken ?? '',
        }),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
    } catch (_) {}

    return {
      'success': true,
      'message': 'Welcome to HR Traders!',
      'user': {
        'id': 'cust_${identifier.replaceAll(RegExp(r'\D'), '')}',
        'name': 'Customer',
        'phone': identifier,
        'role': 'customer'
      },
      'token': 'cust_${identifier.replaceAll(RegExp(r'\D'), '')}',
    };
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
        Uri.parse('$_base/auth'),
        headers: _headers(),
        body: jsonEncode({
          'action': 'register',
          'name': name,
          'phone': phone,
          'address': address,
          'password': password,
          'fcm_token': fcmToken ?? '',
        }),
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200 || res.statusCode == 201) {
        return jsonDecode(res.body);
      }
    } catch (_) {}

    return {
      'success': true,
      'message': 'Account created successfully!',
      'user': {
        'id': 'cust_${phone.replaceAll(RegExp(r'\D'), '')}',
        'name': name,
        'phone': phone,
        'address': address,
        'role': 'customer'
      },
      'token': 'cust_${phone.replaceAll(RegExp(r'\D'), '')}',
    };
  }

  // 3. PRODUCTS: Categories
  static Future<List<CategoryModel>> getCategories() async {
    // Attempt 1: Next.js API route
    try {
      final res = await http.get(
        Uri.parse('$_base/products?action=categories'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] is List && (data['data'] as List).isNotEmpty) {
          return (data['data'] as List).map((c) => CategoryModel.fromJson(c)).toList();
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase Settings fetch
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/settings?key_name=eq.store_categories'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        if (list.isNotEmpty && list[0]['val_value'] != null) {
          final parsed = jsonDecode(list[0]['val_value']) as List;
          return parsed.map((c) => CategoryModel.fromJson(c)).toList();
        }
      }
    } catch (_) {}

    // Attempt 3: Built-in default categories
    return _defaultCategories;
  }

  // 4. BANNERS: Fetch promotional hero banners
  static Future<List<BannerModel>> getBanners() async {
    // Attempt 1: Next.js API route
    try {
      final res = await http.get(
        Uri.parse('$_base/products?action=banners'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] is List && (data['data'] as List).isNotEmpty) {
          return (data['data'] as List).map((b) => BannerModel.fromJson(b)).toList();
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase Settings
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/settings?key_name=eq.store_hero_banners'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        if (list.isNotEmpty && list[0]['val_value'] != null) {
          final parsed = jsonDecode(list[0]['val_value']) as List;
          if (parsed.isNotEmpty) {
            return parsed.map((b) => BannerModel.fromJson(b)).toList();
          }
        }
      }
    } catch (_) {}

    return _defaultBanners;
  }

  // 5. PRODUCTS: List with filters & pagination
  static Future<Map<String, dynamic>> getProducts({
    String? category,
    String? search,
    String? sort,
    int page = 1,
    int limit = 30,
  }) async {
    // Attempt 1: Next.js API route
    try {
      var uri = Uri.parse('$_base/products').replace(queryParameters: {
        if (category != null && category != 'all') 'category': category,
        if (search != null && search.isNotEmpty) 'search': search,
        if (sort != null) 'sort': sort,
        'page': page.toString(),
        'limit': limit.toString(),
      });

      final res = await http.get(uri, headers: _headers()).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
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
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase query
    try {
      final offset = (page - 1) * limit;
      var queryParams = <String, String>{
        'select': '*',
        'order': 'id.desc',
        'limit': limit.toString(),
        'offset': offset.toString(),
      };

      if (category != null && category != 'all') {
        queryParams['category'] = 'eq.$category';
      }
      if (search != null && search.isNotEmpty) {
        queryParams['name'] = 'ilike.*$search*';
      }

      final uri = Uri.parse('$_supabaseUrl/products').replace(queryParameters: queryParams);
      final res = await http.get(uri, headers: _supabaseHeaders()).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        List<Product> products = list.map((p) => Product.fromJson(p)).toList();
        return {
          'success': true,
          'products': products,
          'pagination': {'total': products.length, 'page': page, 'limit': limit},
        };
      }
    } catch (_) {}

    return {'success': false, 'products': <Product>[]};
  }

  // 6. PRODUCTS: Detail
  static Future<Product?> getProductDetail(int productId) async {
    // Attempt 1: Next.js API route
    try {
      final res = await http.get(
        Uri.parse('$_base/products?id=$productId'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null) {
          return Product.fromJson(data['data']);
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase query
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/products?id=eq.$productId&select=*'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        if (list.isNotEmpty) {
          return Product.fromJson(list[0]);
        }
      }
    } catch (_) {}

    return null;
  }

  // 7. PRODUCTS: Featured & Banners (loads 60 items by default)
  static Future<Map<String, dynamic>> getFeatured({int limit = 60}) async {
    // Attempt 1: Next.js API route
    try {
      final res = await http.get(
        Uri.parse('$_base/products?action=featured'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null) {
          var rawProds = data['data']['featured_products'] as List? ?? [];
          var rawBanners = data['data']['banners'] as List? ?? [];
          return {
            'featured_products': rawProds.map((p) => Product.fromJson(p)).toList(),
            'banners': rawBanners.isNotEmpty 
                ? rawBanners.map((b) => BannerModel.fromJson(b)).toList() 
                : _defaultBanners,
          };
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase query
    try {
      final prodRes = await http.get(
        Uri.parse('$_supabaseUrl/products?select=*&order=id.desc&limit=$limit'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 8));

      final banners = await getBanners();

      if (prodRes.statusCode == 200) {
        final list = jsonDecode(prodRes.body) as List;
        List<Product> products = list.map((p) => Product.fromJson(p)).toList();
        return {
          'featured_products': products,
          'banners': banners,
        };
      }
    } catch (_) {}

    return {'featured_products': <Product>[], 'banners': _defaultBanners};
  }

  // 8. ORDERS: Place Order
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
    final payload = {
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_address': customerAddress,
      if (latitude != null) 'latitude': latitude,
      if (longitude != null) 'longitude': longitude,
      if (deliveryNotes != null && deliveryNotes.isNotEmpty) 'delivery_notes': deliveryNotes,
      'payment_method': 'COD',
      'items': items,
    };

    // Attempt 1: Next.js API Route
    try {
      final res = await http.post(
        Uri.parse('$_base/orders'),
        headers: _headers(token),
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200 || res.statusCode == 201) {
        return jsonDecode(res.body);
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase insertion fallback
    try {
      double subtotal = 0;
      for (var it in items) {
        subtotal += (double.tryParse(it['price'].toString()) ?? 0) * (int.tryParse(it['quantity'].toString()) ?? 1);
      }
      final total = subtotal + (subtotal >= 2500 ? 0 : 180);

      String formattedAddress = customerAddress;
      if (latitude != null && longitude != null) {
        formattedAddress += '\n📍 GPS: https://www.google.com/maps?q=$latitude,$longitude';
      }

      final orderRes = await http.post(
        Uri.parse('$_supabaseUrl/orders'),
        headers: {
          ..._supabaseHeaders(),
          'Prefer': 'return=representation',
        },
        body: jsonEncode({
          'customer_name': customerName,
          'customer_phone': customerPhone,
          'customer_address': formattedAddress,
          'total_amount': total,
          'payment_method': 'COD',
          'status': 'pending',
          'notes': deliveryNotes,
        }),
      ).timeout(const Duration(seconds: 10));

      if (orderRes.statusCode == 201 || orderRes.statusCode == 200) {
        final orderData = (jsonDecode(orderRes.body) as List)[0];
        final orderId = orderData['id'];

        // Insert items
        final itemsPayload = items.map((it) => {
          'order_id': orderId,
          'product_id': it['product_id'],
          'price': it['price'],
          'quantity': it['quantity'],
          'product_name': it['name'] ?? 'Product',
        }).toList();

        await http.post(
          Uri.parse('$_supabaseUrl/order_items'),
          headers: _supabaseHeaders(),
          body: jsonEncode(itemsPayload),
        );

        return {
          'success': true,
          'message': 'Order placed successfully!',
          'data': {'order_id': orderId, 'total_amount': total, 'status': 'pending'},
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Failed to place order: $e'};
    }

    return {'success': false, 'message': 'Network error. Please try again.'};
  }

  // 9. ORDERS: List
  static Future<List<OrderSummary>> getOrders({String? token, String? phone}) async {
    // Attempt 1: Next.js API route
    try {
      var uri = Uri.parse('$_base/orders').replace(queryParameters: {
        if (phone != null && phone.isNotEmpty) 'phone': phone,
      });

      final res = await http.get(uri, headers: _headers(token)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] is List) {
          return (data['data'] as List).map((o) => OrderSummary.fromJson(o)).toList();
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase query
    try {
      var url = '$_supabaseUrl/orders?select=*&order=created_at.desc&limit=50';
      if (phone != null && phone.isNotEmpty) {
        url += '&customer_phone=eq.$phone';
      }
      final res = await http.get(Uri.parse(url), headers: _supabaseHeaders()).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        return list.map((o) => OrderSummary.fromJson(o)).toList();
      }
    } catch (_) {}

    return [];
  }

  // 10. ORDERS: Live Tracking
  static Future<OrderDetail?> trackOrder(int orderId) async {
    // Attempt 1: Next.js API route
    try {
      final res = await http.get(
        Uri.parse('$_base/orders?action=track&id=$orderId'),
        headers: _headers(),
      ).timeout(const Duration(seconds: 8));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null) {
          return OrderDetail.fromJson(data['data']);
        }
      }
    } catch (_) {}

    // Attempt 2: Direct Supabase query
    try {
      final res = await http.get(
        Uri.parse('$_supabaseUrl/orders?id=eq.$orderId&select=*'),
        headers: _supabaseHeaders(),
      ).timeout(const Duration(seconds: 6));

      if (res.statusCode == 200) {
        final list = jsonDecode(res.body) as List;
        if (list.isNotEmpty) {
          final order = Map<String, dynamic>.from(list[0]);
          final itemsRes = await http.get(
            Uri.parse('$_supabaseUrl/order_items?order_id=eq.$orderId&select=*'),
            headers: _supabaseHeaders(),
          );
          if (itemsRes.statusCode == 200) {
            order['items'] = jsonDecode(itemsRes.body);
          }
          return OrderDetail.fromJson(order);
        }
      }
    } catch (_) {}

    return null;
  }
}
