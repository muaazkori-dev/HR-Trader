import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/admin_constants.dart';
import '../models/admin_models.dart';
import '../providers/admin_provider.dart';
import '../services/admin_api_service.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final stats = prov.stats;

    return RefreshIndicator(
      onRefresh: () => prov.loadAllData(),
      color: AdminConstants.primary,
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        children: [
          // 1. Gross Revenue Hero Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AdminConstants.primaryDark, AdminConstants.primary],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: AdminConstants.primary.withOpacity(0.3),
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
                    const Text(
                      "TODAY'S GROSS SALES",
                      style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '${stats?.todayOrdersCount ?? 0} Orders Today',
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  'Rs. ${(stats?.todayRevenue ?? 0).toStringAsFixed(0)}',
                  style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 4),
                Text(
                  '${stats?.deliveredTodayCount ?? 0} delivered successfully • Tando Adam Store',
                  style: const TextStyle(color: Colors.white70, fontSize: 11),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 2. Metrics 2x2 Grid
          Row(
            children: [
              Expanded(
                child: _buildMetricTile(
                  icon: Icons.pending_actions,
                  iconColor: Colors.amber.shade700,
                  bgColor: Colors.amber.shade50,
                  label: 'Pending Orders',
                  value: '${stats?.pendingOrdersCount ?? 0}',
                  subtext: 'Awaiting dispatch',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildMetricTile(
                  icon: Icons.warning_amber_rounded,
                  iconColor: Colors.red.shade600,
                  bgColor: Colors.red.shade50,
                  label: 'Low Stock (<5)',
                  value: '${stats?.lowStockCount ?? 0}',
                  subtext: 'Needs purchase order',
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          Row(
            children: [
              Expanded(
                child: _buildMetricTile(
                  icon: Icons.check_circle_outline,
                  iconColor: AdminConstants.primary,
                  bgColor: const Color(0xFFECFDF5),
                  label: 'Delivered Today',
                  value: '${stats?.deliveredTodayCount ?? 0}',
                  subtext: 'Cash collected',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildMetricTile(
                  icon: Icons.inventory_2_outlined,
                  iconColor: Colors.blue.shade700,
                  bgColor: Colors.blue.shade50,
                  label: 'Active Products',
                  value: '${stats?.totalProductsCount ?? 0}',
                  subtext: 'Live on catalog',
                ),
              ),
            ],
          ),

          const SizedBox(height: 20),

          // 3. Recent Orders Section
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: const [
              Text(
                'Recent Store Orders',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AdminConstants.textPrimary),
              ),
              Text(
                'Live Real-time Feed',
                style: TextStyle(fontSize: 11, color: AdminConstants.primaryDark, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 10),

          if (prov.orders.isEmpty)
            Container(
              padding: const EdgeInsets.all(24),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AdminConstants.borderSubtle),
              ),
              child: const Text('No orders received today yet.', style: TextStyle(color: AdminConstants.textSecondary, fontSize: 12)),
            )
          else
            ...prov.orders.take(5).map((order) => _buildRecentOrderTile(context, order, prov)),
        ],
      ),
    );
  }

  Widget _buildMetricTile({
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
    required String label,
    required String value,
    required String subtext,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AdminConstants.borderSubtle),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AdminConstants.textSecondary)),
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(8)),
                child: Icon(icon, color: iconColor, size: 16),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AdminConstants.textPrimary)),
          Text(subtext, style: const TextStyle(fontSize: 10, color: AdminConstants.textSecondary)),
        ],
      ),
    );
  }

  Widget _buildRecentOrderTile(BuildContext context, AdminOrder order, AdminProvider prov) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AdminConstants.borderSubtle),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AdminConstants.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.receipt_outlined, color: AdminConstants.primary, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${order.orderRef} • ${order.customerName}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AdminConstants.textPrimary),
                ),
                Text(
                  '${order.cleanAddress} (${order.paymentMethod})',
                  style: const TextStyle(fontSize: 11, color: AdminConstants.textSecondary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                'Rs. ${order.totalAmount.toStringAsFixed(0)}',
                style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: AdminConstants.primary),
              ),
              const SizedBox(height: 2),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: order.status == 'delivered' ? const Color(0xFFDCFCE7) : Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  order.status.toUpperCase(),
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    color: order.status == 'delivered' ? const Color(0xFF16A34A) : Colors.amber.shade900,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
