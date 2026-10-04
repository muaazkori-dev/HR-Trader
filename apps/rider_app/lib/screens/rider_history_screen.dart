import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../constants/rider_constants.dart';
import '../models/rider_order_model.dart';
import '../providers/rider_orders_provider.dart';
import '../services/rider_api_service.dart';

class RiderHistoryScreen extends StatelessWidget {
  const RiderHistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<RiderOrdersProvider>();
    final deliveredList = prov.deliveredOrders;
    final totalCash = prov.todayCashCollected;

    return Scaffold(
      backgroundColor: RiderConstants.scaffoldBg,
      body: RefreshIndicator(
        onRefresh: () => prov.fetchData(),
        color: RiderConstants.primary,
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          children: [
            // 1. Prominent COD Cash Ledger Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [RiderConstants.primaryDark, RiderConstants.primary],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: RiderConstants.primary.withOpacity(0.3),
                    blurRadius: 12,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: const [
                          Icon(Icons.account_balance_wallet, color: Colors.white, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'CASH IN HAND (COD LEDGER)',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1,
                            ),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          '${deliveredList.length} Delivered',
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  Text(
                    'Rs. ${totalCash.toStringAsFixed(0)}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 4),

                  const Text(
                    'Total cash collected from customers to hand over at HR Traders store counter.',
                    style: TextStyle(color: Colors.white70, fontSize: 11, height: 1.3),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // 2. Deliveries Section Title
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Completed Deliveries',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: RiderConstants.textPrimary),
                ),
                Text(
                  '${deliveredList.length} Total',
                  style: const TextStyle(fontSize: 12, color: RiderConstants.textSecondary, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // 3. Deliveries List
            if (deliveredList.isEmpty)
              Container(
                padding: const EdgeInsets.all(32),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: RiderConstants.borderSubtle),
                ),
                child: Column(
                  children: const [
                    Icon(Icons.receipt_long_outlined, size: 40, color: RiderConstants.textSecondary),
                    SizedBox(height: 10),
                    Text(
                      'No Deliveries Completed Yet',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: RiderConstants.textPrimary),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Deliver active orders to see your collected cash ledger here.',
                      style: TextStyle(fontSize: 12, color: RiderConstants.textSecondary),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              )
            else
              ...deliveredList.map((order) => _buildDeliveredCard(context, order)),
          ],
        ),
      ),
    );
  }

  Widget _buildDeliveredCard(BuildContext context, RiderOrder order) {
    final timeStr = DateFormat('hh:mm a, dd MMM').format(order.createdAt);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: RiderConstants.borderSubtle),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.check_circle, color: RiderConstants.primary, size: 16),
                  const SizedBox(width: 6),
                  Text(
                    order.orderRef,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: RiderConstants.textPrimary),
                  ),
                ],
              ),
              Text(
                'Rs. ${order.totalAmount.toStringAsFixed(0)}',
                style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: RiderConstants.primary),
              ),
            ],
          ),
          const SizedBox(height: 6),

          Text(
            '${order.customerName} • ${order.cleanAddress}',
            style: const TextStyle(fontSize: 12, color: RiderConstants.textSecondary),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 8),

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                timeStr,
                style: const TextStyle(fontSize: 10, color: RiderConstants.textSecondary),
              ),
              Row(
                children: [
                  InkWell(
                    onTap: () => RiderApiService.makePhoneCall(order.customerPhone),
                    child: Row(
                      children: const [
                        Icon(Icons.call, size: 12, color: Colors.blue),
                        SizedBox(width: 4),
                        Text('Call', style: TextStyle(fontSize: 11, color: Colors.blue, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
