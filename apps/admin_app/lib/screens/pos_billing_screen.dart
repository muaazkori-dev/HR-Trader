import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/admin_constants.dart';
import '../models/admin_models.dart';
import '../providers/admin_provider.dart';

class POSBillingScreen extends StatefulWidget {
  const POSBillingScreen({super.key});

  @override
  State<POSBillingScreen> createState() => _POSBillingScreenState();
}

class _POSBillingScreenState extends State<POSBillingScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  final TextEditingController _customerCtrl = TextEditingController(text: 'Walk-in Counter Customer');
  String _searchQuery = '';
  final Map<int, int> _cart = {}; // productId -> quantity
  bool _isProcessing = false;

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final products = prov.products;

    // Filter products
    final filtered = products.where((p) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return p.name.toLowerCase().contains(q) ||
          p.category.toLowerCase().contains(q) ||
          (p.barcode != null && p.barcode!.contains(q));
    }).toList();

    // Calculate cart totals
    double cartTotal = 0;
    int cartCount = 0;
    final List<Map<String, dynamic>> cartItemsPayload = [];

    _cart.forEach((pId, qty) {
      final p = products.firstWhere((prod) => prod.id == pId, orElse: () => AdminProduct(id: pId, name: 'Item', category: 'anaj', price: 0, purchasePrice: 0, stockQuantity: 0, unit: '', image: ''));
      cartTotal += p.price * qty;
      cartCount += qty;
      cartItemsPayload.add({
        'product_id': p.id,
        'name': p.name,
        'price': p.price,
        'quantity': qty,
      });
    });

    return Scaffold(
      backgroundColor: AdminConstants.scaffoldBg,
      body: Column(
        children: [
          // 1. Search Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _searchCtrl,
              onChanged: (v) => setState(() => _searchQuery = v.trim()),
              decoration: InputDecoration(
                hintText: 'Search product by name or scan barcode...',
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
          ),

          // 2. Product Quick Selection List
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(12),
              itemCount: filtered.length,
              itemBuilder: (context, index) {
                final p = filtered[index];
                final qtyInCart = _cart[p.id] ?? 0;

                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: qtyInCart > 0 ? AdminConstants.primary : AdminConstants.borderSubtle),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              p.name,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AdminConstants.textPrimary),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                Text(
                                  'Rs. ${p.price.toStringAsFixed(0)}',
                                  style: const TextStyle(fontWeight: FontWeight.w900, color: AdminConstants.primary, fontSize: 13),
                                ),
                                const SizedBox(width: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                  decoration: BoxDecoration(
                                    color: p.isInStock ? Colors.green.shade50 : Colors.red.shade50,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text(
                                    'Stock: ${p.stockQuantity}',
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                      color: p.isInStock ? Colors.green.shade800 : Colors.red.shade800,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),

                      // Quantity Stepper
                      if (qtyInCart > 0)
                        Row(
                          children: [
                            IconButton.filledTonal(
                              icon: const Icon(Icons.remove, size: 14),
                              style: IconButton.styleFrom(
                                minimumSize: const Size(30, 30),
                                padding: EdgeInsets.zero,
                              ),
                              onPressed: () {
                                setState(() {
                                  if (qtyInCart <= 1) {
                                    _cart.remove(p.id);
                                  } else {
                                    _cart[p.id] = qtyInCart - 1;
                                  }
                                });
                              },
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                              child: Text(
                                '$qtyInCart',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                              ),
                            ),
                            IconButton.filled(
                              icon: const Icon(Icons.add, size: 14),
                              style: IconButton.styleFrom(
                                backgroundColor: AdminConstants.primary,
                                minimumSize: const Size(30, 30),
                                padding: EdgeInsets.zero,
                              ),
                              onPressed: () {
                                setState(() {
                                  _cart[p.id] = qtyInCart + 1;
                                });
                              },
                            ),
                          ],
                        )
                      else
                        ElevatedButton.icon(
                          icon: const Icon(Icons.add_shopping_cart, size: 14),
                          label: const Text('Add', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AdminConstants.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            minimumSize: const Size(60, 32),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: () {
                            setState(() {
                              _cart[p.id] = 1;
                            });
                          },
                        ),
                    ],
                  ),
                );
              },
            ),
          ),

          // 3. Bottom Checkout & Charge Drawer
          if (_cart.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -3)),
                ],
              ),
              child: SafeArea(
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('$cartCount item${cartCount == 1 ? '' : 's'} in POS register', style: const TextStyle(fontSize: 12, color: AdminConstants.textSecondary)),
                        TextButton(
                          onPressed: () => setState(() => _cart.clear()),
                          child: const Text('Clear Cart', style: TextStyle(color: Colors.red, fontSize: 11)),
                        ),
                      ],
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Total Bill:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        Text(
                          'Rs. ${cartTotal.toStringAsFixed(0)}',
                          style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 20, color: AdminConstants.primary),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.payment, size: 18),
                        label: _isProcessing
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : Text('Charge Rs. ${cartTotal.toStringAsFixed(0)} (Cash Sale)', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AdminConstants.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: _isProcessing ? null : () => _handleCompleteSale(context, prov, cartTotal, cartItemsPayload),
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _handleCompleteSale(BuildContext context, AdminProvider prov, double total, List<Map<String, dynamic>> items) async {
    setState(() => _isProcessing = true);

    final success = await prov.processPosSale(
      customerName: _customerCtrl.text.trim(),
      items: items,
    );

    setState(() => _isProcessing = false);

    if (context.mounted && success) {
      setState(() => _cart.clear());
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Row(
            children: const [
              Icon(Icons.check_circle, color: AdminConstants.primary),
              SizedBox(width: 8),
              Text('Sale Completed!'),
            ],
          ),
          content: Text('Received Rs. ${total.toStringAsFixed(0)} cash at counter. Stock decremented in database.'),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AdminConstants.primary, foregroundColor: Colors.white),
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('OK'),
            ),
          ],
        ),
      );
    }
  }
}
