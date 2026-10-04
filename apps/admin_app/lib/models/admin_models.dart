class AdminDashboardStats {
  final double todayRevenue;
  final int todayOrdersCount;
  final int deliveredTodayCount;
  final int pendingOrdersCount;
  final int lowStockCount;
  final int totalProductsCount;

  AdminDashboardStats({
    required this.todayRevenue,
    required this.todayOrdersCount,
    required this.deliveredTodayCount,
    required this.pendingOrdersCount,
    required this.lowStockCount,
    required this.totalProductsCount,
  });

  factory AdminDashboardStats.fromJson(Map<String, dynamic> json) {
    return AdminDashboardStats(
      todayRevenue: double.tryParse(json['today_revenue']?.toString() ?? '0') ?? 0.0,
      todayOrdersCount: json['today_orders_count'] is int ? json['today_orders_count'] : int.tryParse(json['today_orders_count']?.toString() ?? '0') ?? 0,
      deliveredTodayCount: json['delivered_today_count'] is int ? json['delivered_today_count'] : int.tryParse(json['delivered_today_count']?.toString() ?? '0') ?? 0,
      pendingOrdersCount: json['pending_orders_count'] is int ? json['pending_orders_count'] : int.tryParse(json['pending_orders_count']?.toString() ?? '0') ?? 0,
      lowStockCount: json['low_stock_count'] is int ? json['low_stock_count'] : int.tryParse(json['low_stock_count']?.toString() ?? '0') ?? 0,
      totalProductsCount: json['total_products_count'] is int ? json['total_products_count'] : int.tryParse(json['total_products_count']?.toString() ?? '0') ?? 0,
    );
  }
}

class AdminProduct {
  final int id;
  final String name;
  final String category;
  double price;
  double purchasePrice;
  int stockQuantity;
  final String unit;
  final String? barcode;
  final String image;

  AdminProduct({
    required this.id,
    required this.name,
    required this.category,
    required this.price,
    required this.purchasePrice,
    required this.stockQuantity,
    required this.unit,
    this.barcode,
    required this.image,
  });

  bool get isInStock => stockQuantity > 0;
  bool get isLowStock => stockQuantity > 0 && stockQuantity <= 5;

  factory AdminProduct.fromJson(Map<String, dynamic> json) {
    return AdminProduct(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? 'Product',
      category: json['category']?.toString() ?? 'anaj',
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      purchasePrice: double.tryParse(json['purchase_price']?.toString() ?? '0') ?? 0.0,
      stockQuantity: json['stock_quantity'] is int ? json['stock_quantity'] : int.tryParse(json['stock_quantity']?.toString() ?? '0') ?? 0,
      unit: json['unit']?.toString() ?? 'pack',
      barcode: json['barcode']?.toString(),
      image: json['image']?.toString() ?? '',
    );
  }
}

class AdminOrderItem {
  final int id;
  final String productName;
  final double price;
  final int quantity;

  AdminOrderItem({
    required this.id,
    required this.productName,
    required this.price,
    required this.quantity,
  });

  factory AdminOrderItem.fromJson(Map<String, dynamic> json) {
    return AdminOrderItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      productName: json['product_name']?.toString() ?? json['name']?.toString() ?? 'Product',
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      quantity: json['quantity'] is int ? json['quantity'] : int.tryParse(json['quantity']?.toString() ?? '1') ?? 1,
    );
  }
}

class AdminOrder {
  final int id;
  final String orderRef;
  final String customerName;
  final String customerPhone;
  final String rawAddress;
  final String cleanAddress;
  final String? mapUrl;
  final double totalAmount;
  final String paymentMethod;
  String status;
  final String? notes;
  final DateTime createdAt;
  final List<AdminOrderItem> items;

  AdminOrder({
    required this.id,
    required this.orderRef,
    required this.customerName,
    required this.customerPhone,
    required this.rawAddress,
    required this.cleanAddress,
    this.mapUrl,
    required this.totalAmount,
    required this.paymentMethod,
    required this.status,
    this.notes,
    required this.createdAt,
    required this.items,
  });

  factory AdminOrder.fromJson(Map<String, dynamic> json) {
    final id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0;
    final orderRef = json['order_ref']?.toString() ?? '#HRT-${id.toString().padLeft(5, '0')}';
    final customerName = json['customer_name']?.toString() ?? 'Customer';
    final customerPhone = json['customer_phone']?.toString() ?? '';
    final rawAddress = json['customer_address']?.toString() ?? '';
    final notes = json['notes']?.toString();

    // Parse map url
    String? mapUrl;
    final combined = '$rawAddress ${notes ?? ''}';
    final match = RegExp(r'https:\/\/(?:www\.)?(?:google\.com\/maps\?q=|maps\.google\.com\/\?q=)(-?\d+\.\d+),(-?\d+\.\d+)').firstMatch(combined);
    if (match != null) {
      mapUrl = match.group(0);
    }

    // Clean address for display
    String cleaned = rawAddress
        .replaceAll(RegExp(r'📍\s*Live\s*(?:GPS\s*)?Location:?\s*https?:\/\/\S+', caseSensitive: false), '')
        .replaceAll(RegExp(r'📍\s*Live\s*GPS:?\s*https?:\/\/\S+', caseSensitive: false), '')
        .replaceAll(RegExp(r'https?:\/\/(?:www\.)?(?:google\.com\/maps[^\s,)]*|maps\.google\.com[^\s,)]*|\S+)', caseSensitive: false), '')
        .replaceAll(RegExp(r'[\(\[]\s*GPS:[^\]\)]+[\]\)]', caseSensitive: false), '')
        .replaceAll(RegExp(r'\(Lat:[^)]+\)', caseSensitive: false), '')
        .trim();

    if (cleaned.isEmpty) cleaned = rawAddress;

    final rawItems = json['order_items'] as List? ?? json['items'] as List? ?? [];
    final itemsList = rawItems.map((it) => AdminOrderItem.fromJson(it)).toList();

    return AdminOrder(
      id: id,
      orderRef: orderRef,
      customerName: customerName,
      customerPhone: customerPhone,
      rawAddress: rawAddress,
      cleanAddress: cleaned,
      mapUrl: mapUrl,
      totalAmount: double.tryParse(json['total_amount']?.toString() ?? '0') ?? 0.0,
      paymentMethod: json['payment_method']?.toString() ?? 'COD',
      status: json['status']?.toString() ?? 'pending',
      notes: notes,
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() 
          : DateTime.now(),
      items: itemsList,
    );
  }
}

class AdminDemand {
  final int id;
  final String productName;
  final String? customerContact;
  final String? notes;
  final DateTime createdAt;

  AdminDemand({
    required this.id,
    required this.productName,
    this.customerContact,
    this.notes,
    required this.createdAt,
  });

  factory AdminDemand.fromJson(Map<String, dynamic> json) {
    return AdminDemand(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      productName: json['product_name']?.toString() ?? json['name']?.toString() ?? 'Item',
      customerContact: json['customer_contact']?.toString() ?? json['phone']?.toString(),
      notes: json['notes']?.toString(),
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() 
          : DateTime.now(),
    );
  }
}
