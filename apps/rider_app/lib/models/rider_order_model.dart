class RiderOrderItem {
  final int id;
  final String productName;
  final double price;
  final int quantity;

  RiderOrderItem({
    required this.id,
    required this.productName,
    required this.price,
    required this.quantity,
  });

  factory RiderOrderItem.fromJson(Map<String, dynamic> json) {
    return RiderOrderItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      productName: json['product_name']?.toString() ?? json['name']?.toString() ?? 'Product',
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      quantity: json['quantity'] is int ? json['quantity'] : int.tryParse(json['quantity']?.toString() ?? '1') ?? 1,
    );
  }
}

class RiderOrder {
  final int id;
  final String orderRef;
  final String customerName;
  final String customerPhone;
  final String rawAddress;
  final String cleanAddress;
  final double? latitude;
  final double? longitude;
  final String? googleMapsUrl;
  final double totalAmount;
  final String paymentMethod;
  final String status;
  final String? notes;
  final DateTime createdAt;
  final List<RiderOrderItem> items;

  RiderOrder({
    required this.id,
    required this.orderRef,
    required this.customerName,
    required this.customerPhone,
    required this.rawAddress,
    required this.cleanAddress,
    this.latitude,
    this.longitude,
    this.googleMapsUrl,
    required this.totalAmount,
    required this.paymentMethod,
    required this.status,
    this.notes,
    required this.createdAt,
    required this.items,
  });

  factory RiderOrder.fromJson(Map<String, dynamic> json) {
    final id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0;
    final orderRef = json['order_ref']?.toString() ?? '#HRT-${id.toString().padLeft(5, '0')}';
    final customerName = json['customer_name']?.toString() ?? 'Customer';
    final customerPhone = json['customer_phone']?.toString() ?? '';
    final rawAddress = json['customer_address']?.toString() ?? '';
    final notes = json['notes']?.toString();

    // Parse GPS from address / notes / direct fields
    double? lat;
    double? lng;
    String? mapUrl;

    if (json['latitude'] != null && json['longitude'] != null) {
      lat = double.tryParse(json['latitude'].toString());
      lng = double.tryParse(json['longitude'].toString());
      if (lat != null && lng != null) {
        mapUrl = 'https://www.google.com/maps?q=$lat,$lng';
      }
    }

    if (mapUrl == null) {
      final combined = '$rawAddress ${notes ?? ''}';
      final match = RegExp(r'https:\/\/(?:www\.)?(?:google\.com\/maps\?q=|maps\.google\.com\/\?q=)(-?\d+\.\d+),(-?\d+\.\d+)').firstMatch(combined);
      if (match != null) {
        mapUrl = match.group(0);
        lat = double.tryParse(match.group(1) ?? '');
        lng = double.tryParse(match.group(2) ?? '');
      }
    }

    // Clean address for display
    String cleaned = rawAddress
        .replaceAll(RegExp(r'📍\s*Live\s*(?:GPS\s*)?Location:?\s*https?:\/\/\S+', caseSensitive: false), '')
        .replaceAll(RegExp(r'📍\s*Live\s*GPS:?\s*https?:\/\/\S+', caseSensitive: false), '')
        .replaceAll(RegExp(r'https?:\/\/(?:www\.)?(?:google\.com\/maps[^\s,)]*|maps\.google\.com[^\s,)]*|\S+)', caseSensitive: false), '')
        .replaceAll(RegExp(r'[\(\[]\s*GPS:[^\]\)]+[\]\)]', caseSensitive: false), '')
        .replaceAll(RegExp(r'\(Lat:[^)]+\)', caseSensitive: false), '')
        .trim();

    if (cleaned.isEmpty) {
      cleaned = rawAddress;
    }

    // Items
    final rawItems = json['order_items'] as List? ?? json['items'] as List? ?? [];
    final itemsList = rawItems.map((it) => RiderOrderItem.fromJson(it)).toList();

    return RiderOrder(
      id: id,
      orderRef: orderRef,
      customerName: customerName,
      customerPhone: customerPhone,
      rawAddress: rawAddress,
      cleanAddress: cleaned,
      latitude: lat,
      longitude: lng,
      googleMapsUrl: mapUrl,
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
