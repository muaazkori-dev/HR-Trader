import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/admin_constants.dart';
import '../providers/admin_provider.dart';

class StoreSettingsScreen extends StatefulWidget {
  const StoreSettingsScreen({super.key});

  @override
  State<StoreSettingsScreen> createState() => _StoreSettingsScreenState();
}

class _StoreSettingsScreenState extends State<StoreSettingsScreen> {
  final TextEditingController _shippingFeeCtrl = TextEditingController();
  final TextEditingController _minOrderCtrl = TextEditingController();
  final TextEditingController _freeThresholdCtrl = TextEditingController();
  final TextEditingController _riderPhoneCtrl = TextEditingController();
  final TextEditingController _riderNameCtrl = TextEditingController();
  bool _initialized = false;

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final settings = prov.settings;

    if (!_initialized && settings.isNotEmpty) {
      _shippingFeeCtrl.text = settings['shipping_fee'] ?? '100';
      _minOrderCtrl.text = settings['min_order_value'] ?? '0';
      _freeThresholdCtrl.text = settings['free_shipping_threshold'] ?? '2500';
      _riderPhoneCtrl.text = settings['default_rider_phone'] ?? '03033943814';
      _riderNameCtrl.text = settings['default_rider_name'] ?? 'Store Rider';
      _initialized = true;
    }

    return Scaffold(
      backgroundColor: AdminConstants.scaffoldBg,
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        children: [
          // 1. Store Open / Close Status Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdminConstants.borderSubtle),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: (prov.isShopOpen ? Colors.green : Colors.red).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        prov.isShopOpen ? Icons.store : Icons.store_mall_directory_outlined,
                        color: prov.isShopOpen ? Colors.green : Colors.red,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Online Store Status', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        Text(
                          prov.isShopOpen ? 'Online • Taking orders on app' : 'Closed • Checkout disabled',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: prov.isShopOpen ? Colors.green.shade700 : Colors.red.shade700,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                Switch(
                  value: prov.isShopOpen,
                  activeColor: AdminConstants.primary,
                  onChanged: (_) => prov.toggleShopStatus(),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 2. Delivery Rates & Free Shipping Configuration
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdminConstants.borderSubtle),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.local_shipping_outlined, color: AdminConstants.primary, size: 20),
                    SizedBox(width: 8),
                    Text('Delivery Charges & Thresholds', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ],
                ),
                const SizedBox(height: 14),

                TextField(
                  controller: _shippingFeeCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Standard Delivery Charges (Rs.)',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: _freeThresholdCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Free Delivery on Orders Above (Rs.)',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: _minOrderCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Minimum Order Value Allowed (Rs.)',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 14),

                SizedBox(
                  width: double.infinity,
                  height: 42,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AdminConstants.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () async {
                      await prov.updateSetting('shipping_fee', _shippingFeeCtrl.text.trim());
                      await prov.updateSetting('free_shipping_threshold', _freeThresholdCtrl.text.trim());
                      await prov.updateSetting('min_order_value', _minOrderCtrl.text.trim());
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Delivery settings updated successfully!'), backgroundColor: AdminConstants.primary),
                        );
                      }
                    },
                    child: const Text('Save Delivery Settings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Rider Dispatch Configuration
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdminConstants.borderSubtle),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.two_wheeler, color: Color(0xFF7C3AED), size: 20),
                    SizedBox(width: 8),
                    Text('Delivery Rider Dispatch Settings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ],
                ),
                const SizedBox(height: 6),
                const Text(
                  'Default rider phone & name for 1-tap WhatsApp delivery task assignment:',
                  style: TextStyle(fontSize: 11, color: AdminConstants.textSecondary),
                ),
                const SizedBox(height: 14),

                TextField(
                  controller: _riderPhoneCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Default Rider WhatsApp Number',
                    hintText: 'e.g. 03033943814',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: _riderNameCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Default Rider Name',
                    hintText: 'e.g. Ali (Rider)',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 14),

                SizedBox(
                  width: double.infinity,
                  height: 42,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF7C3AED),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () async {
                      await prov.updateSetting('default_rider_phone', _riderPhoneCtrl.text.trim());
                      await prov.updateSetting('default_rider_name', _riderNameCtrl.text.trim());
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Rider dispatch credentials updated!'), backgroundColor: Color(0xFF7C3AED)),
                        );
                      }
                    },
                    child: const Text('Save Rider Settings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 3. Customer Demands Section
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdminConstants.borderSubtle),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: const [
                        Icon(Icons.inventory_outlined, color: Colors.blue, size: 20),
                        SizedBox(width: 8),
                        Text('Customer Item Demands', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(10)),
                      child: Text(
                        '${prov.demands.length} items',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blue.shade800),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                const Text(
                  'Products searched by customers that were missing in stock:',
                  style: TextStyle(fontSize: 11, color: AdminConstants.textSecondary),
                ),
                const SizedBox(height: 10),

                if (prov.demands.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text('No unresolved customer requests right now.', style: TextStyle(fontSize: 11, color: AdminConstants.textSecondary)),
                  )
                else
                  ...prov.demands.take(6).map((d) => Container(
                    margin: const EdgeInsets.only(bottom: 6),
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(d.productName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        Text(d.customerContact ?? 'Guest', style: const TextStyle(fontSize: 10, color: AdminConstants.textSecondary)),
                      ],
                    ),
                  )),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // 4. Branch Locations in Tando Adam
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdminConstants.borderSubtle),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.location_city, color: AdminConstants.primary, size: 20),
                    SizedBox(width: 8),
                    Text('HR Traders Branches', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ],
                ),
                const SizedBox(height: 12),

                // Branch 1
                const Text('Branch 1: Toor Colony (Main Branch)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                const Text('Front of Hira Public School, Tando Adam\nPhone: +92 303 3943814', style: TextStyle(fontSize: 11, color: AdminConstants.textSecondary)),
                const Divider(height: 16),

                // Branch 2
                const Text('Branch 2: Gulshan-e-Sardar', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                const Text('Near Ayoub Hotel, Tando Adam\nPhone: +92 313 7889859', style: TextStyle(fontSize: 11, color: AdminConstants.textSecondary)),
              ],
            ),
          ),

          const SizedBox(height: 24),

          Center(
            child: Text(
              '${AdminConstants.appName} v1.0.0\nHR Traders Tando Adam Ecosystem',
              style: const TextStyle(fontSize: 10, color: AdminConstants.textSecondary),
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(height: 30),
        ],
      ),
    );
  }
}
