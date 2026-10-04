import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/rider_constants.dart';
import '../providers/rider_auth_provider.dart';
import 'rider_login_screen.dart';

class RiderProfileScreen extends StatelessWidget {
  const RiderProfileScreen({super.key});

  Future<void> _callHelpline() async {
    final uri = Uri.parse('tel:${RiderConstants.storePhone}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _openStoreWhatsApp() async {
    final uri = Uri.parse('https://wa.me/${RiderConstants.storeWhatsApp}?text=${Uri.encodeComponent('Assalam o Alaikum HR Traders Manager, Rider here.')}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<RiderAuthProvider>();

    return Scaffold(
      backgroundColor: RiderConstants.scaffoldBg,
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        children: [
          // 1. Rider Profile Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: RiderConstants.borderSubtle),
            ),
            child: Column(
              children: [
                Container(
                  width: 70,
                  height: 70,
                  decoration: BoxDecoration(
                    color: RiderConstants.primary.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Center(
                    child: Icon(Icons.person, size: 40, color: RiderConstants.primary),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  auth.riderName,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: RiderConstants.textPrimary),
                ),
                const SizedBox(height: 2),
                Text(
                  auth.riderPhone,
                  style: const TextStyle(fontSize: 13, color: RiderConstants.textSecondary),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: RiderConstants.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '🛵 Bike: ${auth.bikeNumber}',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: RiderConstants.primaryDark),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 2. Duty Status Switch Tile
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
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
                        color: (auth.isOnline ? const Color(0xFF10B981) : Colors.slate).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(
                        auth.isOnline ? Icons.check_circle : Icons.pause_circle_outline,
                        color: auth.isOnline ? const Color(0xFF10B981) : Colors.slate,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Duty Status', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        Text(
                          auth.isOnline ? 'Online - Receiving orders' : 'Offline - Off duty',
                          style: const TextStyle(fontSize: 11, color: RiderConstants.textSecondary),
                        ),
                      ],
                    ),
                  ],
                ),
                Switch(
                  value: auth.isOnline,
                  activeColor: RiderConstants.primary,
                  onChanged: (_) => auth.toggleDuty(),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 3. Store Helpline & Support
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: RiderConstants.borderSubtle),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Store Management Help',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: RiderConstants.textPrimary),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Contact store manager for stock queries, address clarification, or emergencies.',
                  style: TextStyle(fontSize: 11, color: RiderConstants.textSecondary),
                ),
                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.call, size: 16),
                        label: const Text('Call Store', style: TextStyle(fontSize: 12)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: RiderConstants.primary,
                          side: const BorderSide(color: RiderConstants.primary),
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: _callHelpline,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.chat, size: 16),
                        label: const Text('WhatsApp', style: TextStyle(fontSize: 12)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF16A34A),
                          foregroundColor: Colors.white,
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: _openStoreWhatsApp,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // 4. Logout / Switch Rider Button
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              icon: const Icon(Icons.logout, size: 18, color: Colors.red),
              label: const Text('Log Out & End Duty', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: Colors.red.shade200),
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () async {
                await auth.logout();
                if (context.mounted) {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const RiderLoginScreen()),
                    (route) => false,
                  );
                }
              },
            ),
          ),
          const SizedBox(height: 30),

          // Version info
          Center(
            child: Text(
              '${RiderConstants.appName} v1.0.0\nToor Colony, Tando Adam',
              style: const TextStyle(fontSize: 10, color: RiderConstants.textSecondary),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}
