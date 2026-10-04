import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import '../constants/app_constants.dart';
import '../models/category_model.dart';
import '../models/product.dart';
import '../providers/cart_provider.dart';
import '../services/api_service.dart';
import 'product_detail_screen.dart';

class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  bool _isLoading = true;
  List<CategoryModel> _categories = [];

  @override
  void initState() {
    super.initState();
    _fetchCategories();
  }

  Future<void> _fetchCategories() async {
    final list = await ApiService.getCategories();
    if (mounted) {
      setState(() {
        _categories = list;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppConstants.scaffoldBg,
      appBar: AppBar(
        title: const Text('All Categories', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 17)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppConstants.primaryColor))
          : RefreshIndicator(
              onRefresh: _fetchCategories,
              color: AppConstants.primaryColor,
              child: GridView.builder(
                padding: const EdgeInsets.all(16),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 14,
                  mainAxisSpacing: 14,
                  childAspectRatio: 0.9,
                ),
                itemCount: _categories.length,
                itemBuilder: (context, index) {
                  final cat = _categories[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => CategoryProductsScreen(categorySlug: cat.slug, categoryName: cat.name),
                        ),
                      );
                    },
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppConstants.borderSubtle),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.02),
                            blurRadius: 5,
                            offset: const Offset(0, 2),
                          )
                        ],
                      ),
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Expanded(
                            child: CachedNetworkImage(
                              imageUrl: cat.imageUrl,
                              fit: BoxFit.contain,
                              memCacheWidth: 200,
                              maxWidthDiskCache: 300,
                              fadeInDuration: const Duration(milliseconds: 150),
                              placeholder: (_, __) => const Icon(Icons.category, color: Colors.grey),
                              errorWidget: (_, __, ___) => const Icon(Icons.shopping_bag_outlined, color: AppConstants.primaryColor, size: 40),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            cat.name,
                            textAlign: TextAlign.center,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppConstants.textPrimary),
                          ),
                          const SizedBox(height: 3),
                          Text(
                            '${cat.productCount} Items',
                            style: const TextStyle(fontSize: 11, color: AppConstants.textSecondary),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
    );
  }
}

// ----------------------------------------------------
// Category Products / Search Screen
// ----------------------------------------------------
class CategoryProductsScreen extends StatefulWidget {
  final String categorySlug;
  final String? categoryName;
  final String? initialSearch;

  const CategoryProductsScreen({
    super.key,
    required this.categorySlug,
    this.categoryName,
    this.initialSearch,
  });

  @override
  State<CategoryProductsScreen> createState() => _CategoryProductsScreenState();
}

class _CategoryProductsScreenState extends State<CategoryProductsScreen> {
  bool _isLoading = true;
  List<Product> _products = [];
  String _search = '';
  String _sort = 'latest';
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _search = widget.initialSearch ?? '';
    _searchCtrl.text = _search;
    _fetchProducts();
  }

  Future<void> _fetchProducts() async {
    setState(() => _isLoading = true);
    final res = await ApiService.getProducts(
      category: widget.categorySlug,
      search: _search,
      sort: _sort,
    );

    if (mounted) {
      setState(() {
        _products = res['products'] as List<Product>? ?? [];
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.categoryName ?? (widget.categorySlug == 'all' ? 'Search Results' : widget.categorySlug);

    return Scaffold(
      backgroundColor: AppConstants.scaffoldBg,
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
      ),
      body: Column(
        children: [
          // Filter & Search bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            color: Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    height: 38,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: TextField(
                      controller: _searchCtrl,
                      textInputAction: TextInputAction.search,
                      onSubmitted: (val) {
                        setState(() => _search = val.trim());
                        _fetchProducts();
                      },
                      decoration: const InputDecoration(
                        hintText: 'Filter items...',
                        hintStyle: TextStyle(fontSize: 12),
                        prefixIcon: Icon(Icons.search, size: 18, color: Colors.grey),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 9),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                PopupMenuButton<String>(
                  icon: const Icon(Icons.sort, color: AppConstants.primaryColor),
                  onSelected: (val) {
                    setState(() => _sort = val);
                    _fetchProducts();
                  },
                  itemBuilder: (context) => const [
                    PopupMenuItem(value: 'latest', child: Text('Latest Added')),
                    PopupMenuItem(value: 'price_asc', child: Text('Price: Low to High')),
                    PopupMenuItem(value: 'price_desc', child: Text('Price: High to Low')),
                    PopupMenuItem(value: 'name_asc', child: Text('Name: A to Z')),
                  ],
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppConstants.borderSubtle),

          // Grid
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: AppConstants.primaryColor))
                : _products.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: const [
                            Icon(Icons.search_off, size: 60, color: Colors.grey),
                            SizedBox(height: 12),
                            Text('No products found', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.grey)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _fetchProducts,
                        color: AppConstants.primaryColor,
                        child: GridView.builder(
                          padding: const EdgeInsets.all(16),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            mainAxisSpacing: 12,
                            crossAxisSpacing: 12,
                            childAspectRatio: 0.68,
                          ),
                          itemCount: _products.length,
                          itemBuilder: (context, index) {
                            final prod = _products[index];
                            return _buildCard(prod);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildCard(Product prod) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => ProductDetailScreen(productId: prod.id)),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppConstants.borderSubtle),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: CachedNetworkImage(
                    imageUrl: prod.imageUrl,
                    fit: BoxFit.contain,
                    memCacheWidth: 280,
                    maxWidthDiskCache: 400,
                    fadeInDuration: const Duration(milliseconds: 200),
                    placeholder: (_, __) => Container(
                      color: const Color(0xFFF8FAFC),
                      child: const Center(
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 1.5, color: Color(0xFFCBD5E1)),
                        ),
                      ),
                    ),
                    errorWidget: (_, __, ___) => const Icon(Icons.image_not_supported, color: Colors.grey),
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    prod.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Rs. ${prod.price.toStringAsFixed(0)}',
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: AppConstants.primaryColor),
                      ),
                      Consumer<CartProvider>(
                        builder: (context, cart, _) => InkWell(
                          onTap: () {
                            cart.addItem(prod);
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('${prod.name} added to cart!'),
                                duration: const Duration(seconds: 1),
                                behavior: SnackBarBehavior.floating,
                                backgroundColor: AppConstants.primaryDark,
                              ),
                            );
                          },
                          child: Container(
                            padding: const EdgeInsets.all(5),
                            decoration: BoxDecoration(
                              color: AppConstants.primaryColor,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Icon(Icons.add, color: Colors.white, size: 14),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
