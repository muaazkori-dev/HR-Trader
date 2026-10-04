import 'dart:async';
import 'package:flutter/foundation.dart';
import '../models/rider_order_model.dart';
import '../services/rider_api_service.dart';

class RiderOrdersProvider with ChangeNotifier {
  List<RiderOrder> _activeOrders = [];
  List<RiderOrder> _deliveredOrders = [];
  bool _isLoading = false;
  bool _isUpdating = false;
  Timer? _pollingTimer;

  List<RiderOrder> get activeOrders => _activeOrders;
  List<RiderOrder> get deliveredOrders => _deliveredOrders;
  bool get isLoading => _isLoading;
  bool get isUpdating => _isUpdating;

  int get activeCount => _activeOrders.length;
  int get deliveredCount => _deliveredOrders.length;

  double get todayCashCollected {
    return _deliveredOrders.fold(0.0, (sum, o) => sum + o.totalAmount);
  }

  RiderOrdersProvider() {
    fetchData();
    startAutoSync();
  }

  void startAutoSync() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      fetchData(silent: true);
    });
  }

  void stopAutoSync() {
    _pollingTimer?.cancel();
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  Future<void> fetchData({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final active = await RiderApiService.getActiveDeliveries();
      final history = await RiderApiService.getDeliveryHistory();

      _activeOrders = active;
      _deliveredOrders = history;
    } catch (e) {
      debugPrint('Error fetching rider deliveries: $e');
    } finally {
      if (!silent) {
        _isLoading = false;
      }
      notifyListeners();
    }
  }

  Future<bool> updateStatus(int orderId, String newStatus) async {
    _isUpdating = true;
    notifyListeners();

    final success = await RiderApiService.updateOrderStatus(orderId, newStatus);

    if (success) {
      await fetchData(silent: true);
    }

    _isUpdating = false;
    notifyListeners();
    return success;
  }
}
