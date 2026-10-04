import '../utils/image_utils.dart';

class CategoryModel {
  final String slug;
  final String name;
  final int productCount;
  final String imageUrl;

  CategoryModel({
    required this.slug,
    required this.name,
    required this.productCount,
    required this.imageUrl,
  });

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    final rawImg = (json['image'] ?? json['image_url'] ?? '').toString();
    return CategoryModel(
      slug: (json['slug'] ?? json['id'] ?? '').toString(),
      name: (json['name'] ?? '').toString(),
      productCount: json['product_count'] is int 
          ? json['product_count'] 
          : int.tryParse(json['product_count']?.toString() ?? '0') ?? 0,
      imageUrl: ImageUtils.getOptimizedUrl(rawImg, width: 140, quality: 75),
    );
  }
}
