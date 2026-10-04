import 'product.dart';

class CartItem {
  final Product product;
  int quantity;

  CartItem({
    required this.product,
    this.quantity = 1,
  });

  double get totalPrice => product.price * quantity;

  Map<String, dynamic> toOrderPayload() {
    return {
      'product_id': product.id,
      'quantity': quantity,
      'price': product.price,
      'name': product.name,
      'product_name': product.name,
    };
  }
}
