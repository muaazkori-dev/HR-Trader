import 'package:flutter/foundation.dart';
import '../models/cart_item.dart';
import '../models/product.dart';
import '../services/api_service.dart';

class CartProvider extends ChangeNotifier {
  final Map<int, CartItem> _items = {};

  double _shippingFee = 100.0;
  double _freeShippingThreshold = 2500.0;
  double _minOrderValue = 0.0;
  bool _isShopOpen = true;

  Map<int, CartItem> get items => {..._items};

  int get itemCount => _items.values.fold(0, (sum, item) => sum + item.quantity);

  double get subtotal => _items.values.fold(0.0, (sum, item) => sum + item.totalPrice);

  double get dynamicShippingFeeRate => _shippingFee;
  double get dynamicFreeThresholdRate => _freeShippingThreshold;
  double get dynamicMinOrderValue => _minOrderValue;
  bool get isShopOpen => _isShopOpen;

  double get shippingFee {
    if (_items.isEmpty) return 0.0;
    return subtotal >= _freeShippingThreshold ? 0.0 : _shippingFee;
  }

  double get grandTotal => subtotal + shippingFee;

  bool get isFreeShipping => subtotal >= _freeShippingThreshold;

  double get remainingForFreeShipping => (_freeShippingThreshold - subtotal).clamp(0.0, _freeShippingThreshold);

  CartProvider() {
    loadDynamicSettings();
  }

  Future<void> loadDynamicSettings() async {
    try {
      final settings = await ApiService.getStoreSettings();
      _shippingFee = double.tryParse(settings['shipping_fee']?.toString() ?? '100') ?? 100.0;
      _freeShippingThreshold = double.tryParse(settings['free_shipping_threshold']?.toString() ?? '2500') ?? 2500.0;
      _minOrderValue = double.tryParse(settings['min_order_value']?.toString() ?? '0') ?? 0.0;
      _isShopOpen = (settings['shop_status'] ?? 'open') == 'open';
      notifyListeners();
    } catch (_) {}
  }

  int getQuantity(int productId) {
    return _items[productId]?.quantity ?? 0;
  }

  void addItem(Product product, [int quantity = 1]) {
    if (_items.containsKey(product.id)) {
      _items[product.id]!.quantity += quantity;
    } else {
      _items[product.id] = CartItem(product: product, quantity: quantity);
    }
    notifyListeners();
  }

  void updateQuantity(int productId, int quantity) {
    if (!_items.containsKey(productId)) return;
    if (quantity <= 0) {
      _items.remove(productId);
    } else {
      _items[productId]!.quantity = quantity;
    }
    notifyListeners();
  }

  void decrementItem(int productId) {
    if (!_items.containsKey(productId)) return;
    if (_items[productId]!.quantity > 1) {
      _items[productId]!.quantity -= 1;
    } else {
      _items.remove(productId);
    }
    notifyListeners();
  }

  int quantity(int productId) {
    return _items[productId]?.quantity ?? 0;
  }

  void removeItem(int productId) {
    _items.remove(productId);
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    notifyListeners();
  }
}
