import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/admin_constants.dart';
import '../providers/admin_provider.dart';
import 'dashboard_screen.dart';
import 'orders_desk_screen.dart';
import 'pos_billing_screen.dart';
import 'inventory_desk_screen.dart';
import 'store_settings_screen.dart';

class AdminMainScreen extends StatefulWidget {
  const AdminMainScreen({super.key});

  @override
  State<AdminMainScreen> createState() => _AdminMainScreenState();
}

class _AdminMainScreenState extends State<AdminMainScreen> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    DashboardScreen(),
    OrdersDeskScreen(),
    POSBillingScreen(),
    InventoryDeskScreen(),
    StoreSettingsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();

    return Scaffold(
      backgroundColor: AdminConstants.scaffoldBg,
      appBar: AppBar(
        backgroundColor: AdminConstants.slateDark,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AdminConstants.primary,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.storefront, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'HR TRADERS',
                  style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Colors.white, letterSpacing: 0.5),
                ),
                Text(
                  'Store Owner Admin Desk',
                  style: TextStyle(fontSize: 10, color: Colors.white.withOpacity(0.7)),
                ),
              ],
            ),
          ],
        ),
        actions: [
          // 1-Tap Store Open / Closed Switch
          GestureDetector(
            onTap: () => _confirmShopToggle(context, prov),
            child: Container(
              margin: const EdgeInsets.only(right: 12, top: 10, bottom: 10),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: prov.isShopOpen ? const Color(0xFF10B981) : Colors.red.shade700,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: (prov.isShopOpen ? const Color(0xFF10B981) : Colors.red).withOpacity(0.3),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 7,
                    height: 7,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 5),
                  Text(
                    prov.isShopOpen ? 'SHOP OPEN' : 'CLOSED',
                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 0.5),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // Real-time Order Alert Toast
          if (prov.newOrderAlertMsg != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              color: AdminConstants.primaryDark,
              child: Row(
                children: [
                  const Icon(Icons.notifications_active, color: Colors.amber, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      prov.newOrderAlertMsg!,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                  ),
                  TextButton(
                    onPressed: () {
                      prov.dismissAlert();
                      setState(() => _currentIndex = 1); // Jump to Orders Desk
                    },
                    child: const Text('VIEW', style: TextStyle(color: Colors.amber, fontWeight: FontWeight.bold, fontSize: 12)),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white70, size: 16),
                    onPressed: () => prov.dismissAlert(),
                  ),
                ],
              ),
            ),

          Expanded(
            child: IndexedStack(
              index: _currentIndex,
              children: _screens,
            ),
          ),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (idx) => setState(() => _currentIndex = idx),
        selectedItemColor: AdminConstants.primary,
        unselectedItemColor: AdminConstants.textSecondary,
        backgroundColor: Colors.white,
        elevation: 10,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 10),
        items: [
          const BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_outlined),
            activeIcon: Icon(Icons.dashboard),
            label: 'Overview',
          ),
          BottomNavigationBarItem(
            icon: Badge(
              isLabelVisible: (prov.stats?.pendingOrdersCount ?? 0) > 0,
              label: Text('${prov.stats?.pendingOrdersCount ?? 0}'),
              backgroundColor: AdminConstants.accent,
              child: const Icon(Icons.receipt_long_outlined),
            ),
            activeIcon: Badge(
              isLabelVisible: (prov.stats?.pendingOrdersCount ?? 0) > 0,
              label: Text('${prov.stats?.pendingOrdersCount ?? 0}'),
              backgroundColor: AdminConstants.accent,
              child: const Icon(Icons.receipt_long),
            ),
            label: 'Orders',
          ),
          const BottomNavigationBarItem(
            icon: Icon(Icons.point_of_sale_outlined),
            activeIcon: Icon(Icons.point_of_sale),
            label: 'POS Billing',
          ),
          BottomNavigationBarItem(
            icon: Badge(
              isLabelVisible: (prov.stats?.lowStockCount ?? 0) > 0,
              label: Text('${prov.stats?.lowStockCount ?? 0}'),
              backgroundColor: Colors.red,
              child: const Icon(Icons.inventory_2_outlined),
            ),
            activeIcon: Badge(
              isLabelVisible: (prov.stats?.lowStockCount ?? 0) > 0,
              label: Text('${prov.stats?.lowStockCount ?? 0}'),
              backgroundColor: Colors.red,
              child: const Icon(Icons.inventory_2),
            ),
            label: 'Products',
          ),
          const BottomNavigationBarItem(
            icon: Icon(Icons.settings_outlined),
            activeIcon: Icon(Icons.settings),
            label: 'Settings',
          ),
        ],
      ),
    );
  }

  void _confirmShopToggle(BuildContext context, AdminProvider prov) {
    final willClose = prov.isShopOpen;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(willClose ? 'Close Online Store?' : 'Open Online Store?'),
        content: Text(
          willClose
              ? 'Closing the store will show a "Store Closed" banner on the website and customer mobile app.'
              : 'Opening the store will allow customers to place orders on website and mobile app.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: willClose ? Colors.red : AdminConstants.primary,
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.of(ctx).pop();
              await prov.toggleShopStatus();
            },
            child: Text(willClose ? 'Yes, Close Store' : 'Yes, Open Store'),
          ),
        ],
      ),
    );
  }
}
