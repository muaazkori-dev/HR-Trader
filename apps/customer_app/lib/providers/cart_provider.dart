import 'package:flutter/foundation.dart';
import '../models/cart_item.dart';
import '../models/product.dart';
import '../constants/app_constants.dart';

class CartProvider extends ChangeNotifier {
  final Map<int, CartItem> _items = {};

  Map<int, CartItem> get items => {..._items};

  int get itemCount => _items.values.fold(0, (sum, item) => sum + item.quantity);

  double get subtotal => _items.values.fold(0.0, (sum, item) => sum + item.totalPrice);

  double get shippingFee {
    if (_items.isEmpty) return 0.0;
    return subtotal >= AppConstants.freeShippingThreshold ? 0.0 : AppConstants.defaultShippingFee;
  }

  double get grandTotal => subtotal + shippingFee;

  bool get isFreeShipping => subtotal >= AppConstants.freeShippingThreshold;

  double get remainingForFreeShipping => (AppConstants.freeShippingThreshold - subtotal).clamp(0.0, AppConstants.freeShippingThreshold);

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

  void removeItem(int productId) {
    _items.remove(productId);
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    notifyListeners();
  }
}
