import '../utils/image_utils.dart';

class BannerModel {
  final dynamic id;
  final String tag;
  final String title;
  final String desc;
  final String imageUrl;
  final String link;
  final String theme;

  BannerModel({
    required this.id,
    required this.tag,
    required this.title,
    required this.desc,
    required this.imageUrl,
    required this.link,
    required this.theme,
  });

  factory BannerModel.fromJson(Map<String, dynamic> json) {
    final rawImg = (json['image'] ?? json['image_url'] ?? '').toString();
    return BannerModel(
      id: json['id'] ?? '',
      tag: json['tag']?.toString() ?? 'SPECIAL OFFER',
      title: json['title']?.toString() ?? 'Fresh Groceries & Grains',
      desc: json['desc']?.toString() ?? 'Free delivery on orders above Rs. 2,500',
      imageUrl: ImageUtils.getOptimizedUrl(rawImg, width: 750, quality: 80),
      link: json['link']?.toString() ?? '',
      theme: json['theme']?.toString() ?? 'emerald',
    );
  }
}
