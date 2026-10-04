import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import '../models/admin_models.dart';
import '../services/admin_api_service.dart';

class AdminProvider with ChangeNotifier {
  AdminDashboardStats? _stats;
  List<AdminOrder> _orders = [];
  List<AdminProduct> _products = [];
  Map<String, String> _settings = {};
  List<AdminDemand> _demands = [];

  bool _isLoading = false;
  String _orderFilter = 'all';
  int _lastSeenOrderId = 0;
  String? _newOrderAlertMsg;
  Timer? _syncTimer;

  AdminDashboardStats? get stats => _stats;
  List<AdminOrder> get orders => _orders;
  List<AdminProduct> get products => _products;
  Map<String, String> get settings => _settings;
  List<AdminDemand> get demands => _demands;
  bool get isLoading => _isLoading;
  String get orderFilter => _orderFilter;
  String? get newOrderAlertMsg => _newOrderAlertMsg;

  bool get isShopOpen => (_settings['shop_status'] ?? 'open') == 'open';
  String get storePhone => _settings['store_phone'] ?? '+923033943814';

  AdminProvider() {
    loadAllData();
    _startSyncTimer();
  }

  void _startSyncTimer() {
    _syncTimer?.cancel();
    _syncTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      silentSync();
    });
  }

  void dismissAlert() {
    _newOrderAlertMsg = null;
    notifyListeners();
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    super.dispose();
  }

  // 1. Initial Full Load
  Future<void> loadAllData() async {
    _isLoading = true;
    notifyListeners();

    try {
      final results = await Future.wait([
        AdminApiService.getDashboardStats(),
        AdminApiService.getOrders(status: _orderFilter),
        AdminApiService.getProducts(),
        AdminApiService.getSettings(),
        AdminApiService.getDemands(),
      ]);

      _stats = results[0] as AdminDashboardStats;
      _orders = results[1] as List<AdminOrder>;
      _products = results[2] as List<AdminProduct>;
      _settings = results[3] as Map<String, String>;
      _demands = results[4] as List<AdminDemand>;

      if (_orders.isNotEmpty) {
        _lastSeenOrderId = _orders.first.id;
      }
    } catch (e) {
      debugPrint('Admin load error: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // 2. Silent Sync (Checks for new orders and plays sound alert)
  Future<void> silentSync() async {
    try {
      final newOrders = await AdminApiService.getOrders(status: _orderFilter);
      final newStats = await AdminApiService.getDashboardStats();

      if (newOrders.isNotEmpty && _lastSeenOrderId > 0) {
        final newestId = newOrders.first.id;
        if (newestId > _lastSeenOrderId) {
          // Play notification chime sound and vibrate!
          SystemSound.play(SystemSoundType.alert);
          HapticFeedback.heavyImpact();

          final latest = newOrders.first;
          _newOrderAlertMsg = '🔔 New Order ${latest.orderRef} placed by ${latest.customerName} (Rs. ${latest.totalAmount.toStringAsFixed(0)})!';
        }
      }

      _orders = newOrders;
      _stats = newStats;
      if (_orders.isNotEmpty) {
        _lastSeenOrderId = _orders.first.id;
      }
      notifyListeners();
    } catch (_) {}
  }

  // 3. Filter Orders
  Future<void> setOrderFilter(String filter) async {
    _orderFilter = filter;
    _isLoading = true;
    notifyListeners();

    _orders = await AdminApiService.getOrders(status: filter);
    _isLoading = false;
    notifyListeners();
  }

  // 4. Update Order Status
  Future<bool> updateOrderStatus(int orderId, String newStatus) async {
    final success = await AdminApiService.updateOrderStatus(orderId, newStatus);
    if (success) {
      final idx = _orders.indexWhere((o) => o.id == orderId);
      if (idx != -1) {
        _orders[idx].status = newStatus;
        notifyListeners();
      }
      silentSync();
    }
    return success;
  }

  // 5. Toggle Product Stock (In-Stock / Out-of-Stock)
  Future<bool> toggleStock(int productId, bool inStock) async {
    final success = await AdminApiService.toggleStock(productId, inStock);
    if (success) {
      final idx = _products.indexWhere((p) => p.id == productId);
      if (idx != -1) {
        _products[idx].stockQuantity = inStock ? 15 : 0;
        notifyListeners();
      }
      _stats = await AdminApiService.getDashboardStats();
      notifyListeners();
    }
    return success;
  }

  // 6. Quick Price & Stock Edit
  Future<bool> updateProductQuick({
    required int productId,
    required double price,
    required int stockQuantity,
    required double purchasePrice,
  }) async {
    final success = await AdminApiService.updateProductQuick(
      productId: productId,
      price: price,
      stockQuantity: stockQuantity,
      purchasePrice: purchasePrice,
    );
    if (success) {
      final idx = _products.indexWhere((p) => p.id == productId);
      if (idx != -1) {
        _products[idx].price = price;
        _products[idx].stockQuantity = stockQuantity;
        _products[idx].purchasePrice = purchasePrice;
        notifyListeners();
      }
    }
    return success;
  }

  // 7. Add Product
  Future<bool> addProduct(Map<String, dynamic> data) async {
    final success = await AdminApiService.addProduct(data);
    if (success) {
      _products = await AdminApiService.getProducts();
      _stats = await AdminApiService.getDashboardStats();
      notifyListeners();
    }
    return success;
  }

  // 8. Toggle Shop Open / Close
  Future<bool> toggleShopStatus() async {
    final current = isShopOpen;
    final newVal = current ? 'closed' : 'open';
    final success = await AdminApiService.updateSetting('shop_status', newVal);
    if (success) {
      _settings['shop_status'] = newVal;
      notifyListeners();
    }
    return success;
  }

  // 9. Update Any Setting
  Future<bool> updateSetting(String key, String value) async {
    final success = await AdminApiService.updateSetting(key, value);
    if (success) {
      _settings[key] = value;
      notifyListeners();
    }
    return success;
  }

  // 10. Process POS Counter Sale
  Future<bool> processPosSale({
    String customerName = 'Walk-in Counter Customer',
    required List<Map<String, dynamic>> items,
  }) async {
    final success = await AdminApiService.processPosSale(customerName: customerName, items: items);
    if (success) {
      await silentSync();
      _products = await AdminApiService.getProducts();
      notifyListeners();
    }
    return success;
  }
}
