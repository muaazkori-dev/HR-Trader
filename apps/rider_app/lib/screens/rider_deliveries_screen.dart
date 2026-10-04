import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/rider_constants.dart';
import '../models/rider_order_model.dart';
import '../providers/rider_orders_provider.dart';
import '../services/rider_api_service.dart';

class RiderDeliveriesScreen extends StatelessWidget {
  const RiderDeliveriesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<RiderOrdersProvider>();

    return Scaffold(
      backgroundColor: RiderConstants.scaffoldBg,
      body: RefreshIndicator(
        onRefresh: () => prov.fetchData(),
        color: RiderConstants.primary,
        child: prov.isLoading && prov.activeOrders.isEmpty
            ? const Center(child: CircularProgressIndicator(color: RiderConstants.primary))
            : prov.activeOrders.isEmpty
                ? _buildEmptyState(context, prov)
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    itemCount: prov.activeOrders.length + 1,
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        return _buildHeaderSummary(context, prov);
                      }
                      final order = prov.activeOrders[index - 1];
                      return _buildOrderDeliveryCard(context, order, prov);
                    },
                  ),
      ),
    );
  }

  // ----------------------------------------------------
  // Top Header Summary
  // ----------------------------------------------------
  Widget _buildHeaderSummary(BuildContext context, RiderOrdersProvider prov) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: RiderConstants.borderSubtle),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: RiderConstants.primary.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.two_wheeler, color: RiderConstants.primary, size: 22),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Active Deliveries',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: RiderConstants.textPrimary),
                  ),
                  Text(
                    '${prov.activeCount} order${prov.activeCount == 1 ? '' : 's'} assigned to deliver',
                    style: const TextStyle(fontSize: 11, color: RiderConstants.textSecondary),
                  ),
                ],
              ),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.refresh, color: RiderConstants.primary),
            onPressed: () => prov.fetchData(),
            tooltip: 'Refresh orders',
          ),
        ],
      ),
    );
  }

  // ----------------------------------------------------
  // Individual Order Delivery Card
  // ----------------------------------------------------
  Widget _buildOrderDeliveryCard(BuildContext context, RiderOrder order, RiderOrdersProvider prov) {
    final hasGps = order.latitude != null && order.longitude != null;
    final isOutForDelivery = order.status == 'out_for_delivery';

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isOutForDelivery ? RiderConstants.primary : RiderConstants.borderSubtle,
          width: isOutForDelivery ? 1.5 : 1.0,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Card Header: Ref, Time & Status
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isOutForDelivery ? const Color(0xFFECFDF5) : const Color(0xFFF8FAFC),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(13)),
              border: Border(bottom: BorderSide(color: RiderConstants.borderSubtle)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Text(
                      order.orderRef,
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: RiderConstants.textPrimary),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade50,
                        borderRadius: BorderRadius.circular(4),
                        border: Border.all(color: Colors.amber.shade300),
                      ),
                      child: Text(
                        order.paymentMethod,
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.amber.shade900),
                      ),
                    ),
                  ],
                ),
                _buildStatusBadge(order.status),
              ],
            ),
          ),

          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 2. Customer Name & Action Buttons
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            order.customerName,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: RiderConstants.textPrimary),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            order.customerPhone,
                            style: const TextStyle(fontSize: 12, color: RiderConstants.textSecondary, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),

                    // Quick Call Button
                    IconButton.filledTonal(
                      icon: const Icon(Icons.call, size: 18),
                      style: IconButton.styleFrom(
                        backgroundColor: Colors.blue.shade50,
                        foregroundColor: Colors.blue.shade700,
                      ),
                      onPressed: () => RiderApiService.makePhoneCall(order.customerPhone),
                      tooltip: 'Call Customer',
                    ),
                    const SizedBox(width: 6),

                    // Quick WhatsApp Button
                    IconButton.filledTonal(
                      icon: const Icon(Icons.chat_bubble_outline, size: 18),
                      style: IconButton.styleFrom(
                        backgroundColor: const Color(0xFFDCFCE7),
                        foregroundColor: const Color(0xFF16A34A),
                      ),
                      onPressed: () => RiderApiService.sendWhatsAppMessage(
                        phone: order.customerPhone,
                        orderRef: order.orderRef,
                        customerName: order.customerName,
                        totalAmount: order.totalAmount,
                      ),
                      tooltip: 'WhatsApp Customer',
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                // 3. Delivery Address
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: RiderConstants.borderSubtle),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.location_on, color: RiderConstants.primary, size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              order.cleanAddress,
                              style: const TextStyle(fontSize: 12, color: RiderConstants.textPrimary, height: 1.3),
                            ),
                            if (hasGps) ...[
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  const Icon(Icons.gps_fixed, size: 11, color: RiderConstants.primary),
                                  const SizedBox(width: 4),
                                  Text(
                                    'Live GPS Pin Attached (${order.latitude!.toStringAsFixed(4)}, ${order.longitude!.toStringAsFixed(4)})',
                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: RiderConstants.primaryDark),
                                  ),
                                ],
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                if (order.notes != null && order.notes!.trim().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.amber.shade50,
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: Colors.amber.shade200),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline, size: 14, color: Colors.amber),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Note: ${order.notes}',
                            style: TextStyle(fontSize: 11, color: Colors.amber.shade900, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 12),

                // 4. Items Summary
                if (order.items.isNotEmpty) ...[
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: RiderConstants.borderSubtle),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Items (${order.items.length})',
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: RiderConstants.textSecondary),
                            ),
                            const Text('Qty', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: RiderConstants.textSecondary)),
                          ],
                        ),
                        const Divider(height: 10),
                        ...order.items.map((it) => Padding(
                          padding: const EdgeInsets.symmetric(vertical: 2),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  it.productName,
                                  style: const TextStyle(fontSize: 12, color: RiderConstants.textPrimary),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Text(
                                '${it.quantity}x',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: RiderConstants.textPrimary),
                              ),
                            ],
                          ),
                        )),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                ],

                // 5. Total Cash to Collect
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFA7F3D0)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'CASH TO COLLECT (COD):',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: RiderConstants.primaryDark),
                      ),
                      Text(
                        'Rs. ${order.totalAmount.toStringAsFixed(0)}',
                        style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900, color: RiderConstants.primary),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 14),

                // 6. Action Buttons
                Row(
                  children: [
                    // Open Google Maps GPS Navigation
                    Expanded(
                      flex: 4,
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.directions, size: 16),
                        label: const Text('Navigate (Maps)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: RiderConstants.primary,
                          side: const BorderSide(color: RiderConstants.primary),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: () => RiderApiService.launchNavigation(
                          latitude: order.latitude,
                          longitude: order.longitude,
                          mapUrl: order.googleMapsUrl,
                          address: order.cleanAddress,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Primary State Button (Pick Up or Delivered)
                    Expanded(
                      flex: 5,
                      child: isOutForDelivery
                          ? ElevatedButton.icon(
                              icon: const Icon(Icons.check_circle_outline, size: 16),
                              label: const Text('Mark Delivered', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: RiderConstants.primary,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () => _confirmDelivered(context, order, prov),
                            )
                          : ElevatedButton.icon(
                              icon: const Icon(Icons.two_wheeler, size: 16),
                              label: const Text('Pick Up & Start', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.blue.shade700,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () => _markOutForDelivery(context, order, prov),
                            ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ----------------------------------------------------
  // Status Badge Helper
  // ----------------------------------------------------
  Widget _buildStatusBadge(String status) {
    Color bg;
    Color fg;
    String label;

    switch (status) {
      case 'out_for_delivery':
        bg = const Color(0xFFEDE9FE);
        fg = const Color(0xFF7C3AED);
        label = '🛵 Out for Delivery';
        break;
      case 'packaging':
        bg = const Color(0xFFDBEAFE);
        fg = const Color(0xFF2563EB);
        label = '📦 Packaging';
        break;
      case 'pending':
      default:
        bg = const Color(0xFFFEF3C7);
        fg = const Color(0xFFD97706);
        label = '⏳ New Order';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: fg),
      ),
    );
  }

  // ----------------------------------------------------
  // Actions: Pick Up & Delivered Dialogs
  // ----------------------------------------------------
  void _markOutForDelivery(BuildContext context, RiderOrder order, RiderOrdersProvider prov) async {
    final success = await prov.updateStatus(order.id, 'out_for_delivery');
    if (context.mounted && success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Order ${order.orderRef} is now Out for Delivery!'),
          backgroundColor: RiderConstants.primaryDark,
        ),
      );
    }
  }

  void _confirmDelivered(BuildContext context, RiderOrder order, RiderOrdersProvider prov) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: const [
            Icon(Icons.payments_outlined, color: RiderConstants.primary),
            SizedBox(width: 8),
            Text('Collect Cash & Deliver', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Did you hand over order ${order.orderRef} to ${order.customerName}?'),
            const SizedBox(height: 14),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFECFDF5),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFA7F3D0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Cash to Collect (COD):', style: TextStyle(fontSize: 11, color: RiderConstants.textSecondary)),
                  const SizedBox(height: 2),
                  Text(
                    'Rs. ${order.totalAmount.toStringAsFixed(0)}',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: RiderConstants.primary),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: RiderConstants.primary,
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.of(ctx).pop();
              final success = await prov.updateStatus(order.id, 'delivered');
              if (context.mounted && success) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('✅ Order ${order.orderRef} marked as Delivered! Cash collected: Rs. ${order.totalAmount.toStringAsFixed(0)}'),
                    backgroundColor: RiderConstants.primary,
                  ),
                );
              }
            },
            child: const Text('Confirm Cash Collected'),
          ),
        ],
      ),
    );
  }

  // ----------------------------------------------------
  // Empty State
  // ----------------------------------------------------
  Widget _buildEmptyState(BuildContext context, RiderOrdersProvider prov) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: RiderConstants.primary.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle_outline, color: RiderConstants.primary, size: 45),
            ),
            const SizedBox(height: 18),
            const Text(
              'All Caught Up!',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: RiderConstants.textPrimary),
            ),
            const SizedBox(height: 6),
            const Text(
              'No pending deliveries right now.\nKeep the app open — new orders will appear automatically.',
              style: TextStyle(fontSize: 12, color: RiderConstants.textSecondary, height: 1.4),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),
            OutlinedButton.icon(
              icon: const Icon(Icons.refresh, size: 16),
              label: const Text('Check for New Orders'),
              style: OutlinedButton.styleFrom(foregroundColor: RiderConstants.primary),
              onPressed: () => prov.fetchData(),
            ),
          ],
        ),
      ),
    );
  }
}
