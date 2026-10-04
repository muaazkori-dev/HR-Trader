import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/admin_constants.dart';
import '../models/admin_models.dart';
import '../providers/admin_provider.dart';
import '../services/admin_api_service.dart';

class OrdersDeskScreen extends StatefulWidget {
  const OrdersDeskScreen({super.key});

  @override
  State<OrdersDeskScreen> createState() => _OrdersDeskScreenState();
}

class _OrdersDeskScreenState extends State<OrdersDeskScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';

  final List<Map<String, String>> _statusTabs = [
    {'id': 'all', 'label': 'All Orders'},
    {'id': 'pending', 'label': 'Pending'},
    {'id': 'packaging', 'label': 'Packaging'},
    {'id': 'out_for_delivery', 'label': 'Out for Delivery'},
    {'id': 'delivered', 'label': 'Delivered'},
    {'id': 'cancelled', 'label': 'Cancelled'},
  ];

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final currentFilter = prov.orderFilter;

    // Filter orders by search query
    final filtered = prov.orders.where((o) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return o.orderRef.toLowerCase().contains(q) ||
          o.customerName.toLowerCase().contains(q) ||
          o.customerPhone.contains(q) ||
          o.cleanAddress.toLowerCase().contains(q);
    }).toList();

    return Scaffold(
      backgroundColor: AdminConstants.scaffoldBg,
      body: Column(
        children: [
          // 1. Search Bar & Status Filter Horizontal Carousel
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
            child: Column(
              children: [
                TextField(
                  controller: _searchCtrl,
                  onChanged: (v) => setState(() => _searchQuery = v.trim()),
                  decoration: InputDecoration(
                    hintText: 'Search order #HRT, name, phone...',
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
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  ),
                ),
                const SizedBox(height: 10),

                // Status Filter Chips
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _statusTabs.map((tab) {
                      final isSelected = currentFilter == tab['id'];
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: FilterChip(
                          label: Text(
                            tab['label']!,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                              color: isSelected ? Colors.white : AdminConstants.textPrimary,
                            ),
                          ),
                          selected: isSelected,
                          selectedColor: AdminConstants.primary,
                          backgroundColor: const Color(0xFFF1F5F9),
                          checkmarkColor: Colors.white,
                          onSelected: (_) => prov.setOrderFilter(tab['id']!),
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ],
            ),
          ),

          // 2. Orders List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => prov.loadAllData(),
              color: AdminConstants.primary,
              child: prov.isLoading && prov.orders.isEmpty
                  ? const Center(child: CircularProgressIndicator(color: AdminConstants.primary))
                  : filtered.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: const [
                              Icon(Icons.inbox_outlined, size: 45, color: AdminConstants.textSecondary),
                              SizedBox(height: 10),
                              Text('No matching orders found.', style: TextStyle(fontSize: 13, color: AdminConstants.textSecondary)),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: filtered.length,
                          itemBuilder: (context, index) {
                            return _buildOrderCard(context, filtered[index], prov);
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOrderCard(BuildContext context, AdminOrder order, AdminProvider prov) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AdminConstants.borderSubtle),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: AdminConstants.primary.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(Icons.receipt_long, color: AdminConstants.primary, size: 20),
        ),
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(order.orderRef, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13)),
            _buildStatusBadge(order.status),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 2),
            Text(
              '${order.customerName} • Rs. ${order.totalAmount.toStringAsFixed(0)}',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AdminConstants.textPrimary),
            ),
            Text(
              order.cleanAddress,
              style: const TextStyle(fontSize: 11, color: AdminConstants.textSecondary),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
        children: [
          const Divider(height: 16),

          // Items summary table
          if (order.items.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AdminConstants.borderSubtle),
              ),
              child: Column(
                children: order.items.map((it) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 2),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          '${it.quantity}x ${it.productName}',
                          style: const TextStyle(fontSize: 12, color: AdminConstants.textPrimary),
                        ),
                      ),
                      Text(
                        'Rs. ${(it.price * it.quantity).toStringAsFixed(0)}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                )).toList(),
              ),
            ),
            const SizedBox(height: 10),
          ],

          // Total Bill row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total Amount:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              Text(
                'Rs. ${order.totalAmount.toStringAsFixed(0)} (${order.paymentMethod})',
                style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: AdminConstants.primary),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Action shortcuts
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.call, size: 14),
                  label: const Text('Call', style: TextStyle(fontSize: 11)),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 8)),
                  onPressed: () => AdminApiService.callCustomer(order.customerPhone),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.chat, size: 14, color: Color(0xFF16A34A)),
                  label: const Text('WhatsApp', style: TextStyle(fontSize: 11, color: Color(0xFF16A34A))),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 8)),
                  onPressed: () => AdminApiService.sendWhatsApp(
                    phone: order.customerPhone,
                    message: 'Hello ${order.customerName}, your HR Traders order ${order.orderRef} is received!',
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.map, size: 14),
                  label: const Text('GPS Map', style: TextStyle(fontSize: 11)),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 8)),
                  onPressed: () => AdminApiService.openMap(order.mapUrl, order.cleanAddress),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Change Status Dropdown Selector
          Row(
            children: [
              const Text('Status:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
              const SizedBox(width: 10),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AdminConstants.borderSubtle),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: order.status,
                      isExpanded: true,
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AdminConstants.textPrimary),
                      items: const [
                        DropdownMenuItem(value: 'pending', child: Text('⏳ Pending (New Order)')),
                        DropdownMenuItem(value: 'packaging', child: Text('📦 Packaging in Store')),
                        DropdownMenuItem(value: 'out_for_delivery', child: Text('🛵 Out for Delivery')),
                        DropdownMenuItem(value: 'delivered', child: Text('✅ Delivered')),
                        DropdownMenuItem(value: 'cancelled', child: Text('❌ Cancelled')),
                      ],
                      onChanged: (newStatus) {
                        if (newStatus != null) {
                          prov.updateOrderStatus(order.id, newStatus);
                        }
                      },
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg;
    Color fg;
    String label;

    switch (status) {
      case 'delivered':
        bg = const Color(0xFFDCFCE7);
        fg = const Color(0xFF16A34A);
        label = 'DELIVERED';
        break;
      case 'out_for_delivery':
        bg = const Color(0xFFEDE9FE);
        fg = const Color(0xFF7C3AED);
        label = 'OUT ON BIKE';
        break;
      case 'packaging':
        bg = const Color(0xFFDBEAFE);
        fg = const Color(0xFF2563EB);
        label = 'PACKAGING';
        break;
      case 'cancelled':
        bg = const Color(0xFFFEE2E2);
        fg = const Color(0xFFDC2626);
        label = 'CANCELLED';
        break;
      case 'pending':
      default:
        bg = const Color(0xFFFEF3C7);
        fg = const Color(0xFFD97706);
        label = 'PENDING';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(4)),
      child: Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: fg)),
    );
  }
}
