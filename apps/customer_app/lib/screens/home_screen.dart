import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../constants/app_constants.dart';
import '../models/category_model.dart';
import '../models/product.dart';
import '../models/banner_model.dart';
import '../providers/cart_provider.dart';
import '../services/api_service.dart';
import 'product_detail_screen.dart';
import 'categories_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _isLoading = true;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  int _currentPage = 1;
  static const int _pageSize = 30;

  List<CategoryModel> _categories = [];
  List<Product> _products = [];
  List<BannerModel> _banners = [];

  final TextEditingController _searchCtrl = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final PageController _bannerPageController = PageController();
  int _currentBannerIndex = 0;
  Timer? _bannerTimer;

  @override
  void initState() {
    super.initState();
    _loadHomeData();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _bannerTimer?.cancel();
    _bannerPageController.dispose();
    _scrollController.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 350) {
      if (!_isLoadingMore && _hasMore && !_isLoading) {
        _loadMoreProducts();
      }
    }
  }

  Future<void> _loadHomeData() async {
    setState(() {
      _isLoading = true;
      _currentPage = 1;
      _hasMore = true;
    });

    try {
      final results = await Future.wait([
        ApiService.getCategories(),
        ApiService.getFeatured(limit: 60),
      ]);

      if (mounted) {
        final catList = results[0] as List<CategoryModel>;
        final featuredMap = results[1] as Map<String, dynamic>;
        final prods = featuredMap['featured_products'] as List<Product>? ?? [];
        final banners = featuredMap['banners'] as List<BannerModel>? ?? [];

        setState(() {
          _categories = catList;
          _products = prods;
          _banners = banners.isNotEmpty ? banners : ApiService.getBannersSync();
          _isLoading = false;
          if (prods.length < 30) {
            _hasMore = false;
          }
        });

        _startBannerAutoSlide();

        // Background pre-cache first batch of product images for zero load delay
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) {
            for (var i = 0; i < prods.length && i < 15; i++) {
              if (prods[i].imageUrl.isNotEmpty) {
                precacheImage(CachedNetworkImageProvider(prods[i].imageUrl), context);
              }
            }
          }
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _startBannerAutoSlide() {
    _bannerTimer?.cancel();
    if (_banners.length <= 1) return;

    _bannerTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (_bannerPageController.hasClients) {
        final nextIndex = (_currentBannerIndex + 1) % _banners.length;
        _bannerPageController.animateToPage(
          nextIndex,
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  Future<void> _loadMoreProducts() async {
    setState(() => _isLoadingMore = true);
    final nextPage = _currentPage + 1;

    try {
      final result = await ApiService.getProducts(page: nextPage, limit: _pageSize);
      if (mounted) {
        final newItems = result['products'] as List<Product>? ?? [];
        setState(() {
          _currentPage = nextPage;
          if (newItems.isNotEmpty) {
            _products.addAll(newItems);
          }
          if (newItems.length < _pageSize) {
            _hasMore = false;
          }
          _isLoadingMore = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingMore = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppConstants.scaffoldBg,
      appBar: AppBar(
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.location_on, color: Colors.amberAccent, size: 14),
                const SizedBox(width: 4),
                Text(
                  AppConstants.appName,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.5),
                ),
              ],
            ),
            const Text(
              'Delivering in Tando Adam',
              style: TextStyle(fontSize: 11, color: Colors.white70, fontWeight: FontWeight.normal),
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppConstants.primaryColor))
          : RefreshIndicator(
              color: AppConstants.primaryColor,
              onRefresh: _loadHomeData,
              child: SingleChildScrollView(
                controller: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. Top Search Bar
                    _buildSearchBar(),

                    // 2. Full-Width Edge-to-Edge Hero Banner Carousel
                    _buildHeroBanner(),

                    const SizedBox(height: 12),

                    // 3. Categories Strip
                    _buildCategoriesHeader(),
                    _buildCategoriesList(),

                    const SizedBox(height: 16),

                    // 4. Products Section Header
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'All Groceries & Products',
                            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppConstants.textPrimary),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppConstants.primaryColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              '${_products.length} items',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppConstants.primaryColor),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // 5. Products Grid
                    _buildProductsGrid(),

                    // 6. Loading More Spinner
                    if (_isLoadingMore)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 20),
                        child: Center(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppConstants.primaryColor),
                              ),
                              SizedBox(width: 10),
                              Text('Loading more groceries...', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                            ],
                          ),
                        ),
                      ),

                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
    );
  }

  // Search Bar
  Widget _buildSearchBar() {
    return Container(
      color: AppConstants.primaryColor,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
      child: Container(
        height: 44,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 6,
              offset: const Offset(0, 2),
            )
          ],
        ),
        child: TextField(
          controller: _searchCtrl,
          textInputAction: TextInputAction.search,
          onSubmitted: (val) {
            if (val.trim().isNotEmpty) {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => CategoryProductsScreen(categorySlug: 'all', initialSearch: val.trim()),
                ),
              );
            }
          },
          decoration: const InputDecoration(
            hintText: 'Search daal, rice, oil, soap, snacks...',
            hintStyle: TextStyle(fontSize: 13, color: AppConstants.textSecondary),
            prefixIcon: Icon(Icons.search, color: AppConstants.primaryColor, size: 20),
            border: InputBorder.none,
            contentPadding: EdgeInsets.symmetric(vertical: 12),
          ),
        ),
      ),
    );
  }

  // Full-Width Hero Banner Carousel (Edge-to-Edge Pure Page)
  Widget _buildHeroBanner() {
    final slides = _banners.isNotEmpty ? _banners : ApiService.getBannersSync();

    return SizedBox(
      width: double.infinity,
      height: 185,
      child: Stack(
        children: [
          PageView.builder(
            controller: _bannerPageController,
            onPageChanged: (idx) {
              setState(() => _currentBannerIndex = idx);
            },
            itemCount: slides.length,
            itemBuilder: (context, index) {
              final banner = slides[index];
              final hasImage = banner.imageUrl.isNotEmpty && !banner.imageUrl.contains('placeholder');

              return GestureDetector(
                onTap: () {
                  if (banner.link.contains('category=')) {
                    final cat = banner.link.split('category=').last;
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => CategoryProductsScreen(categorySlug: cat)),
                    );
                  } else {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const CategoriesScreen()),
                    );
                  }
                },
                child: Container(
                  width: double.infinity,
                  height: 185,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: banner.theme == 'cyan'
                          ? [const Color(0xFF0E7490), const Color(0xFF155E75)]
                          : banner.theme == 'amber'
                              ? [const Color(0xFFB45309), const Color(0xFF78350F)]
                              : [const Color(0xFF047857), const Color(0xFF064E3B)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                  ),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      // Background Image (if available)
                      if (hasImage)
                        CachedNetworkImage(
                          imageUrl: banner.imageUrl,
                          fit: BoxFit.cover,
                          width: double.infinity,
                          height: 185,
                          memCacheWidth: 750,
                          maxWidthDiskCache: 800,
                          fadeInDuration: const Duration(milliseconds: 200),
                          placeholder: (_, __) => Container(color: const Color(0xFF064E3B)),
                          errorWidget: (_, __, ___) => const SizedBox(),
                        ),

                      // Gradient Overlay for text contrast
                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [
                              Colors.black.withOpacity(0.75),
                              Colors.black.withOpacity(0.35),
                              Colors.transparent,
                            ],
                            begin: Alignment.centerLeft,
                            end: Alignment.centerRight,
                          ),
                        ),
                      ),

                      // Banner Content
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: banner.theme == 'amber' ? Colors.white : Colors.amber,
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                banner.tag.toUpperCase(),
                                style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.black87),
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              banner.title,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 19,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                                height: 1.2,
                                shadows: [Shadow(color: Colors.black45, blurRadius: 4, offset: Offset(0, 1))],
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              banner.desc,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11,
                                color: Colors.white70,
                                height: 1.2,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppConstants.primaryColor,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    'Shop Now',
                                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white),
                                  ),
                                  SizedBox(width: 4),
                                  Icon(Icons.arrow_forward, size: 10, color: Colors.white),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),

          // Dots Indicator
          if (slides.length > 1)
            Positioned(
              bottom: 8,
              right: 20,
              child: Row(
                children: List.generate(
                  slides.length,
                  (i) => Container(
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: i == _currentBannerIndex ? 16 : 6,
                    height: 6,
                    decoration: BoxDecoration(
                      color: i == _currentBannerIndex ? Colors.white : Colors.white38,
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  // Categories Header (Fixed arrow)
  Widget _buildCategoriesHeader() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Text(
            'Explore Categories',
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppConstants.textPrimary),
          ),
          GestureDetector(
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const CategoriesScreen()),
              );
            },
            child: const Row(
              children: [
                Text(
                  'See All',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppConstants.primaryColor),
                ),
                SizedBox(width: 4),
                Icon(Icons.arrow_forward_ios, size: 11, color: AppConstants.primaryColor),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Categories Horizontal List
  Widget _buildCategoriesList() {
    return Container(
      height: 98,
      margin: const EdgeInsets.only(top: 10),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _categories.length,
        itemBuilder: (context, index) {
          final cat = _categories[index];
          return GestureDetector(
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => CategoryProductsScreen(categorySlug: cat.slug, categoryName: cat.name)),
              );
            },
            child: Container(
              width: 82,
              margin: const EdgeInsets.symmetric(horizontal: 4),
              child: Column(
                children: [
                  Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppConstants.borderSubtle),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.02),
                          blurRadius: 3,
                          offset: const Offset(0, 1),
                        )
                      ],
                    ),
                    padding: const EdgeInsets.all(6),
                    child: CachedNetworkImage(
                      imageUrl: cat.imageUrl,
                      fit: BoxFit.contain,
                      memCacheWidth: 140,
                      maxWidthDiskCache: 200,
                      fadeInDuration: const Duration(milliseconds: 150),
                      placeholder: (_, __) => const Center(child: SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 1.5, color: Color(0xFFCBD5E1)))),
                      errorWidget: (_, __, ___) => const Icon(Icons.shopping_bag_outlined, color: AppConstants.primaryColor, size: 24),
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    cat.name,
                    maxLines: 2,
                    textAlign: TextAlign.center,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppConstants.textPrimary, height: 1.1),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // Products Grid
  Widget _buildProductsGrid() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 0.68,
        ),
        itemCount: _products.length,
        itemBuilder: (context, index) {
          final prod = _products[index];
          return _buildProductCard(prod);
        },
      ),
    );
  }

  // Single Product Card with discount tag & old price
  Widget _buildProductCard(Product prod) {
    final hasDiscount = prod.discountPercentage != null && prod.discountPercentage! > 0;
    final hasOldPrice = prod.oldPrice != null && prod.oldPrice! > prod.price;

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
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.02),
              blurRadius: 4,
              offset: const Offset(0, 2),
            )
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image with Badges
            Expanded(
              child: Stack(
                children: [
                  Center(
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

                  // Discount Badge
                  if (hasDiscount)
                    Positioned(
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFDC2626), // Red 600
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          '-${prod.discountPercentage}% OFF',
                          style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white),
                        ),
                      ),
                    )
                  else if (prod.weight.isNotEmpty && prod.weight != '1')
                    Positioned(
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          '${prod.weight} ${prod.unit}',
                          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black87),
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // Product Details
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    prod.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppConstants.textPrimary),
                  ),
                  const SizedBox(height: 6),

                  // Price Row with Old Price
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Rs. ${prod.price.toStringAsFixed(0)}',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w900,
                              color: AppConstants.primaryColor,
                            ),
                          ),
                          if (hasOldPrice)
                            Text(
                              'Rs. ${prod.oldPrice!.toStringAsFixed(0)}',
                              style: const TextStyle(
                                fontSize: 10,
                                color: AppConstants.textSecondary,
                                decoration: TextDecoration.lineThrough,
                              ),
                            ),
                        ],
                      ),

                      // Quick Add to Cart button
                      Consumer<CartProvider>(
                        builder: (context, cart, _) {
                          return InkWell(
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
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: AppConstants.primaryColor,
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Icon(Icons.add, color: Colors.white, size: 16),
                            ),
                          );
                        },
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
