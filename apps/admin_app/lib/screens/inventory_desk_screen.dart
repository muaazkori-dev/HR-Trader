import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../constants/admin_constants.dart';
import '../models/admin_models.dart';
import '../providers/admin_provider.dart';

class InventoryDeskScreen extends StatefulWidget {
  const InventoryDeskScreen({super.key});

  @override
  State<InventoryDeskScreen> createState() => _InventoryDeskScreenState();
}

class _InventoryDeskScreenState extends State<InventoryDeskScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';
  String _selectedCategory = 'all';

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final products = prov.products;

    // Filter products
    final filtered = products.where((p) {
      if (_selectedCategory != 'all' && p.category != _selectedCategory) {
        return false;
      }
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return p.name.toLowerCase().contains(q) ||
          p.category.toLowerCase().contains(q) ||
          (p.barcode != null && p.barcode!.contains(q));
    }).toList();

    return Scaffold(
      backgroundColor: AdminConstants.scaffoldBg,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AdminConstants.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add),
        label: const Text('Add Product', style: TextStyle(fontWeight: FontWeight.bold)),
        onPressed: () => _openAddProductModal(context, prov),
      ),
      body: Column(
        children: [
          // 1. Search Bar & Category Filter
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Column(
              children: [
                TextField(
                  controller: _searchCtrl,
                  onChanged: (v) => setState(() => _searchQuery = v.trim()),
                  decoration: InputDecoration(
                    hintText: 'Search 381+ products by name, barcode...',
                    hintStyle: const TextStyle(fontSize: 12, color: AdminConstants.textSecondary),
                    prefixIcon: const Icon(Icons.search, size: 18),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 16),
                            onPressed: () {
                              _searchCtrl.clear();
                              setState(() => _searchQuery = '');
                            },
                          )
                        : null,
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: BorderSide(color: AdminConstants.borderSubtle),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                ),
                const SizedBox(height: 8),

                // Category Filter Chips
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(right: 6),
                        child: ChoiceChip(
                          label: const Text('All Products', style: TextStyle(fontSize: 11)),
                          selected: _selectedCategory == 'all',
                          selectedColor: AdminConstants.primary,
                          labelStyle: TextStyle(
                            color: _selectedCategory == 'all' ? Colors.white : AdminConstants.textPrimary,
                            fontWeight: _selectedCategory == 'all' ? FontWeight.bold : FontWeight.normal,
                          ),
                          onSelected: (_) => setState(() => _selectedCategory = 'all'),
                        ),
                      ),
                      ...AdminConstants.categoryLabels.entries.map((e) {
                        final isSel = _selectedCategory == e.key;
                        return Padding(
                          padding: const EdgeInsets.only(right: 6),
                          child: ChoiceChip(
                            label: Text(e.value, style: const TextStyle(fontSize: 11)),
                            selected: isSel,
                            selectedColor: AdminConstants.primary,
                            labelStyle: TextStyle(
                              color: isSel ? Colors.white : AdminConstants.textPrimary,
                              fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
                            ),
                            onSelected: (_) => setState(() => _selectedCategory = e.key),
                          ),
                        );
                      }),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // 2. Product Count Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Showing ${filtered.length} products',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AdminConstants.textSecondary),
                ),
                const Text('Stock Switch (Live Toggle)', style: TextStyle(fontSize: 11, color: AdminConstants.primaryDark, fontWeight: FontWeight.bold)),
              ],
            ),
          ),

          // 3. Products List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.loadAllData(),
              color: AdminConstants.primary,
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 80),
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  return _buildProductCard(context, filtered[index], prov);
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProductCard(BuildContext context, AdminProduct prod, AdminProvider prov) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: prod.isLowStock ? Colors.red.shade200 : AdminConstants.borderSubtle),
      ),
      child: Row(
        children: [
          // Thumbnail
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Container(
              width: 55,
              height: 55,
              color: const Color(0xFFF1F5F9),
              child: prod.image.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: prod.image,
                      fit: BoxFit.contain,
                      placeholder: (_, __) => const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                      errorWidget: (_, __, ___) => const Icon(Icons.image_not_supported, size: 24, color: Colors.grey),
                    )
                  : const Icon(Icons.shopping_bag_outlined, color: Colors.grey),
            ),
          ),
          const SizedBox(width: 12),

          // Product Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  prod.name,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AdminConstants.textPrimary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Text(
                      'Rs. ${prod.price.toStringAsFixed(0)}',
                      style: const TextStyle(fontWeight: FontWeight.w900, color: AdminConstants.primary, fontSize: 13),
                    ),
                    if (prod.purchasePrice > 0) ...[
                      const SizedBox(width: 6),
                      Text(
                        '(Cost: Rs. ${prod.purchasePrice.toStringAsFixed(0)})',
                        style: const TextStyle(fontSize: 10, color: AdminConstants.textSecondary),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: prod.isInStock ? (prod.isLowStock ? Colors.amber.shade50 : Colors.green.shade50) : Colors.red.shade50,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        prod.isInStock ? (prod.isLowStock ? 'LOW STOCK (${prod.stockQuantity})' : 'IN STOCK (${prod.stockQuantity})') : 'OUT OF STOCK',
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                          color: prod.isInStock ? (prod.isLowStock ? Colors.amber.shade900 : Colors.green.shade800) : Colors.red.shade800,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    InkWell(
                      onTap: () => _openQuickEditModal(context, prod, prov),
                      child: const Text('Edit Price/Qty', style: TextStyle(fontSize: 11, color: Colors.blue, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // 1-Tap Quick In-Stock Switch
          Switch(
            value: prod.isInStock,
            activeColor: AdminConstants.primary,
            onChanged: (val) => prov.toggleStock(prod.id, val),
          ),
        ],
      ),
    );
  }

  // Quick Price / Stock Editor Modal
  void _openQuickEditModal(BuildContext context, AdminProduct prod, AdminProvider prov) {
    final priceCtrl = TextEditingController(text: prod.price.toStringAsFixed(0));
    final costCtrl = TextEditingController(text: prod.purchasePrice.toStringAsFixed(0));
    final stockCtrl = TextEditingController(text: prod.stockQuantity.toString());

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Edit: ${prod.name}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold), maxLines: 1),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: priceCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Selling Price (Rs.)', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: costCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Cost / Purchase Price (Rs.)', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: stockCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Stock Quantity', border: OutlineInputBorder()),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AdminConstants.primary, foregroundColor: Colors.white),
            onPressed: () async {
              final newPrice = double.tryParse(priceCtrl.text.trim()) ?? prod.price;
              final newCost = double.tryParse(costCtrl.text.trim()) ?? prod.purchasePrice;
              final newStock = int.tryParse(stockCtrl.text.trim()) ?? prod.stockQuantity;

              Navigator.of(ctx).pop();
              await prov.updateProductQuick(
                productId: prod.id,
                price: newPrice,
                stockQuantity: newStock,
                purchasePrice: newCost,
              );
            },
            child: const Text('Save Changes'),
          ),
        ],
      ),
    );
  }

  // Add Product Modal
  void _openAddProductModal(BuildContext context, AdminProvider prov) {
    final nameCtrl = TextEditingController();
    final priceCtrl = TextEditingController();
    final costCtrl = TextEditingController();
    final stockCtrl = TextEditingController(text: '15');
    final barcodeCtrl = TextEditingController();
    String category = 'anaj';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 20),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Add New Product to Store', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 14),

                TextField(
                  controller: nameCtrl,
                  decoration: const InputDecoration(labelText: 'Product Name *', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),

                // Category selector
                DropdownButtonFormField<String>(
                  value: category,
                  decoration: const InputDecoration(labelText: 'Category', border: OutlineInputBorder()),
                  items: AdminConstants.categoryLabels.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                      .toList(),
                  onChanged: (v) {
                    if (v != null) setModalState(() => category = v);
                  },
                ),
                const SizedBox(height: 12),

                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: priceCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: 'Selling Price *', border: OutlineInputBorder()),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: costCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: 'Cost Price', border: OutlineInputBorder()),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: stockCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: 'Stock Qty', border: OutlineInputBorder()),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: barcodeCtrl,
                        decoration: const InputDecoration(labelText: 'Barcode (Optional)', border: OutlineInputBorder()),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),

                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: AdminConstants.primary, foregroundColor: Colors.white),
                    onPressed: () async {
                      if (nameCtrl.text.trim().isEmpty || priceCtrl.text.trim().isEmpty) return;
                      Navigator.of(ctx).pop();
                      await prov.addProduct({
                        'name': nameCtrl.text.trim(),
                        'category': category,
                        'price': double.tryParse(priceCtrl.text.trim()) ?? 0,
                        'purchase_price': double.tryParse(costCtrl.text.trim()) ?? 0,
                        'stock_quantity': int.tryParse(stockCtrl.text.trim()) ?? 15,
                        'barcode': barcodeCtrl.text.trim().isNotEmpty ? barcodeCtrl.text.trim() : null,
                        'unit': 'pack',
                      });
                    },
                    child: const Text('Add to Store Catalog', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
