import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/rider_constants.dart';
import '../providers/rider_auth_provider.dart';
import '../providers/rider_orders_provider.dart';
import 'rider_deliveries_screen.dart';
import 'rider_history_screen.dart';
import 'rider_profile_screen.dart';

class RiderMainScreen extends StatefulWidget {
  const RiderMainScreen({super.key});

  @override
  State<RiderMainScreen> createState() => _RiderMainScreenState();
}

class _RiderMainScreenState extends State<RiderMainScreen> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    RiderDeliveriesScreen(),
    RiderHistoryScreen(),
    RiderProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<RiderAuthProvider>();
    final ordersProv = context.watch<RiderOrdersProvider>();

    return Scaffold(
      backgroundColor: RiderConstants.scaffoldBg,
      appBar: AppBar(
        backgroundColor: RiderConstants.primaryDark,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.15),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.two_wheeler, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    auth.riderName,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                  ),
                  Text(
                    'Bike: ${auth.bikeNumber} • Tando Adam',
                    style: TextStyle(fontSize: 10, color: Colors.white.withOpacity(0.8)),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          // Online / Offline Duty Switch Pill
          GestureDetector(
            onTap: () => auth.toggleDuty(),
            child: Container(
              margin: const EdgeInsets.only(right: 12, top: 10, bottom: 10),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: auth.isOnline ? const Color(0xFF10B981) : Colors.grey.shade700,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: (auth.isOnline ? const Color(0xFF10B981) : Colors.black).withOpacity(0.3),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    auth.isOnline ? 'ON DUTY' : 'OFFLINE',
                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 0.5),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (idx) => setState(() => _currentIndex = idx),
        selectedItemColor: RiderConstants.primary,
        unselectedItemColor: RiderConstants.textSecondary,
        backgroundColor: Colors.white,
        elevation: 8,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 11),
        items: [
          BottomNavigationBarItem(
            icon: Badge(
              isLabelVisible: ordersProv.activeCount > 0,
              label: Text('${ordersProv.activeCount}', style: const TextStyle(fontWeight: FontWeight.bold)),
              backgroundColor: RiderConstants.accent,
              child: const Icon(Icons.delivery_dining_outlined),
            ),
            activeIcon: Badge(
              isLabelVisible: ordersProv.activeCount > 0,
              label: Text('${ordersProv.activeCount}', style: const TextStyle(fontWeight: FontWeight.bold)),
              backgroundColor: RiderConstants.accent,
              child: const Icon(Icons.delivery_dining),
            ),
            label: 'Deliveries',
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.account_balance_wallet_outlined),
            activeIcon: const Icon(Icons.account_balance_wallet),
            label: 'Cash Ledger',
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.person_outline),
            activeIcon: const Icon(Icons.person),
            label: 'My Profile',
          ),
        ],
      ),
    );
  }
}
