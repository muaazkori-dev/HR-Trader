class OrderSummary {
  final int id;
  final String orderRef;
  final double totalAmount;
  final String status;
  final int itemCount;
  final String paymentMethod;
  final String createdAt;

  OrderSummary({
    required this.id,
    required this.orderRef,
    required this.totalAmount,
    required this.status,
    required this.itemCount,
    required this.paymentMethod,
    required this.createdAt,
  });

  factory OrderSummary.fromJson(Map<String, dynamic> json) {
    return OrderSummary(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      orderRef: json['order_ref']?.toString() ?? '',
      totalAmount: json['total_amount'] != null ? double.tryParse(json['total_amount'].toString()) ?? 0.0 : 0.0,
      status: json['status']?.toString() ?? 'pending',
      itemCount: json['item_count'] is int ? json['item_count'] : int.tryParse(json['item_count']?.toString() ?? '0') ?? 0,
      paymentMethod: json['payment_method']?.toString() ?? 'COD',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class OrderDetail {
  final int orderId;
  final String orderRef;
  final String status;
  final Map<String, dynamic> stepInfo;
  final String customerName;
  final String customerPhone;
  final String customerAddress;
  final double? deliveryLat;
  final double? deliveryLng;
  final double totalAmount;
  final String paymentMethod;
  final String createdAt;
  final RiderInfo? rider;
  final List<OrderItemDetail> items;

  OrderDetail({
    required this.orderId,
    required this.orderRef,
    required this.status,
    required this.stepInfo,
    required this.customerName,
    required this.customerPhone,
    required this.customerAddress,
    this.deliveryLat,
    this.deliveryLng,
    required this.totalAmount,
    required this.paymentMethod,
    required this.createdAt,
    this.rider,
    required this.items,
  });

  factory OrderDetail.fromJson(Map<String, dynamic> json) {
    var rawItems = json['items'] as List? ?? [];
    List<OrderItemDetail> itemList = rawItems.map((i) => OrderItemDetail.fromJson(i)).toList();

    RiderInfo? riderObj;
    if (json['rider'] != null && json['rider'] is Map<String, dynamic>) {
      riderObj = RiderInfo.fromJson(json['rider']);
    }

    return OrderDetail(
      orderId: json['order_id'] is int ? json['order_id'] : int.tryParse(json['order_id'].toString()) ?? 0,
      orderRef: json['order_ref']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      stepInfo: json['step_info'] is Map<String, dynamic> ? json['step_info'] : {},
      customerName: json['customer_name']?.toString() ?? '',
      customerPhone: json['customer_phone']?.toString() ?? '',
      customerAddress: json['customer_address']?.toString() ?? '',
      deliveryLat: json['delivery_lat'] != null ? double.tryParse(json['delivery_lat'].toString()) : null,
      deliveryLng: json['delivery_lng'] != null ? double.tryParse(json['delivery_lng'].toString()) : null,
      totalAmount: json['total_amount'] != null ? double.tryParse(json['total_amount'].toString()) ?? 0.0 : 0.0,
      paymentMethod: json['payment_method']?.toString() ?? 'COD',
      createdAt: json['created_at']?.toString() ?? '',
      rider: riderObj,
      items: itemList,
    );
  }
}

class RiderInfo {
  final String name;
  final String phone;
  final double? currentLat;
  final double? currentLng;
  final String? locationUpdatedAt;

  RiderInfo({
    required this.name,
    required this.phone,
    this.currentLat,
    this.currentLng,
    this.locationUpdatedAt,
  });

  factory RiderInfo.fromJson(Map<String, dynamic> json) {
    return RiderInfo(
      name: json['name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      currentLat: json['current_lat'] != null ? double.tryParse(json['current_lat'].toString()) : null,
      currentLng: json['current_lng'] != null ? double.tryParse(json['current_lng'].toString()) : null,
      locationUpdatedAt: json['location_updated_at']?.toString(),
    );
  }
}

class OrderItemDetail {
  final int productId;
  final String name;
  final String? weight;
  final String? unit;
  final double price;
  final int quantity;
  final double lineTotal;
  final String imageUrl;

  OrderItemDetail({
    required this.productId,
    required this.name,
    this.weight,
    this.unit,
    required this.price,
    required this.quantity,
    required this.lineTotal,
    required this.imageUrl,
  });

  factory OrderItemDetail.fromJson(Map<String, dynamic> json) {
    return OrderItemDetail(
      productId: json['product_id'] is int ? json['product_id'] : int.tryParse(json['product_id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      weight: json['weight']?.toString(),
      unit: json['unit']?.toString(),
      price: json['price'] != null ? double.tryParse(json['price'].toString()) ?? 0.0 : 0.0,
      quantity: json['quantity'] is int ? json['quantity'] : int.tryParse(json['quantity']?.toString() ?? '1') ?? 1,
      lineTotal: json['line_total'] != null ? double.tryParse(json['line_total'].toString()) ?? 0.0 : 0.0,
      imageUrl: json['image_url']?.toString() ?? '',
    );
  }
}
