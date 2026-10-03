class Product {
  final int id;
  final String barcode;
  final String name;
  final String description;
  final double price;
  final int stockQuantity;
  final String weight;
  final String unit;
  final String category;
  final String imageUrl;
  final bool inStock;

  Product({
    required this.id,
    required this.barcode,
    required this.name,
    required this.description,
    required this.price,
    required this.stockQuantity,
    required this.weight,
    required this.unit,
    required this.category,
    required this.imageUrl,
    required this.inStock,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      barcode: json['barcode']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      price: json['price'] != null ? double.tryParse(json['price'].toString()) ?? 0.0 : 0.0,
      stockQuantity: json['stock_quantity'] is int 
          ? json['stock_quantity'] 
          : int.tryParse(json['stock_quantity']?.toString() ?? '0') ?? 0,
      weight: json['weight']?.toString() ?? '',
      unit: json['unit']?.toString() ?? 'pcs',
      category: json['category']?.toString() ?? '',
      imageUrl: json['image_url']?.toString() ?? (json['image']?.toString() ?? ''),
      inStock: json['in_stock'] == true || (json['stock_quantity'] != null && int.tryParse(json['stock_quantity'].toString())! > 0),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'barcode': barcode,
      'name': name,
      'description': description,
      'price': price,
      'stock_quantity': stockQuantity,
      'weight': weight,
      'unit': unit,
      'category': category,
      'image_url': imageUrl,
      'in_stock': inStock,
    };
  }
}
