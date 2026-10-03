import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import '../constants/app_constants.dart';
import '../models/product.dart';
import '../providers/cart_provider.dart';
import '../services/api_service.dart';

class ProductDetailScreen extends StatefulWidget {
  final int productId;
  const ProductDetailScreen({super.key, required this.productId});

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  bool _isLoading = true;
  Product? _product;
  int _quantity = 1;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    setState(() => _isLoading = true);
    final p = await ApiService.getProductDetail(widget.productId);
    if (mounted) {
      setState(() {
        _product = p;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(backgroundColor: AppConstants.primaryColor, elevation: 0),
        body: const Center(child: CircularProgressIndicator(color: AppConstants.primaryColor)),
      );
    }

    if (_product == null) {
      return Scaffold(
        appBar: AppBar(backgroundColor: AppConstants.primaryColor, elevation: 0),
        body: const Center(child: Text('Product not found')),
      );
    }

    final p = _product!;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(p.name, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Big Image Container
            Container(
              height: 280,
              width: double.infinity,
              color: Colors.white,
              padding: const EdgeInsets.all(20),
              child: Center(
                child: CachedNetworkImage(
                  imageUrl: p.imageUrl,
                  fit: BoxFit.contain,
                  placeholder: (_, __) => const CircularProgressIndicator(strokeWidth: 2),
                  errorWidget: (_, __, ___) => const Icon(Icons.image_not_supported, size: 80, color: Colors.grey),
                ),
              ),
            ),
            const Divider(height: 1, color: AppConstants.borderSubtle),

            // Product Details
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: p.inStock ? const Color(0xFFECFDF5) : Colors.red.shade50,
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: p.inStock ? const Color(0xFFA7F3D0) : Colors.red.shade200),
                        ),
                        child: Text(
                          p.inStock ? 'IN STOCK' : 'OUT OF STOCK',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: p.inStock ? AppConstants.primaryDark : Colors.red.shade700,
                          ),
                        ),
                      ),
                      if (p.weight.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            p.weight,
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.black87),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Name
                  Text(
                    p.name,
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppConstants.textPrimary),
                  ),
                  const SizedBox(height: 8),

                  // Price
                  Text(
                    'Rs. ${p.price.toStringAsFixed(0)}',
                    style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: AppConstants.primaryColor),
                  ),
                  const SizedBox(height: 16),

                  // Description
                  if (p.description.isNotEmpty) ...[
                    const Text(
                      'Product Details',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppConstants.textPrimary),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      p.description,
                      style: const TextStyle(fontSize: 13, color: AppConstants.textSecondary, height: 1.5),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // HR Traders Quality Guarantee
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppConstants.scaffoldBg,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppConstants.borderSubtle),
                    ),
                    child: Row(
                      children: const [
                        Icon(Icons.verified_outlined, color: AppConstants.primaryColor, size: 28),
                        SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('100% Genuine & Fresh', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              Text('Directly sourced from trusted suppliers in Tando Adam', style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: AppConstants.borderSubtle)),
        ),
        child: Row(
          children: [
            // Quantity Counter
            Container(
              decoration: BoxDecoration(
                border: Border.all(color: AppConstants.borderSubtle),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.remove, size: 18),
                    onPressed: () {
                      if (_quantity > 1) setState(() => _quantity--);
                    },
                  ),
                  Text('$_quantity', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  IconButton(
                    icon: const Icon(Icons.add, size: 18),
                    onPressed: () {
                      if (_quantity < p.stockQuantity) setState(() => _quantity++);
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(width: 14),

            // Add to Cart Button
            Expanded(
              child: SizedBox(
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppConstants.primaryColor,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    elevation: 0,
                  ),
                  icon: const Icon(Icons.shopping_bag_outlined, size: 18),
                  label: Text(
                    'Add To Cart • Rs. ${(p.price * _quantity).toStringAsFixed(0)}',
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                  ),
                  onPressed: p.inStock
                      ? () {
                          context.read<CartProvider>().addItem(p, _quantity);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('$_quantity x ${p.name} added to cart!'),
                              backgroundColor: AppConstants.primaryDark,
                              behavior: SnackBarBehavior.floating,
                            ),
                          );
                        }
                      : null,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
