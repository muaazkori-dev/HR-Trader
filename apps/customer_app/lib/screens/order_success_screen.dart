import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/app_constants.dart';
import 'main_screen.dart';
import 'order_tracking_screen.dart';

class OrderSuccessScreen extends StatelessWidget {
  final int orderId;
  final String orderRef;
  final double totalAmount;
  final String customerName;
  final String customerPhone;
  final String customerAddress;

  const OrderSuccessScreen({
    super.key,
    required this.orderId,
    required this.orderRef,
    required this.totalAmount,
    required this.customerName,
    required this.customerPhone,
    required this.customerAddress,
  });

  Future<void> _sendWhatsAppConfirmation() async {
    final text = 'Hello HR Traders! I just placed an order:\n\n'
        '🛍️ Order Ref: $orderRef\n'
        '👤 Name: $customerName\n'
        '📞 Phone: $customerPhone\n'
        '📍 Address: $customerAddress\n'
        '💰 Total: Rs. ${totalAmount.toStringAsFixed(0)} (COD)\n\n'
        'Please confirm my delivery!';

    final uri = Uri.parse('https://wa.me/${AppConstants.storeWhatsApp}?text=${Uri.encodeComponent(text)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 30),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Success Icon Circle
              Container(
                width: 90,
                height: 90,
                decoration: BoxDecoration(
                  color: const Color(0xFFECFDF5),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xFFA7F3D0), width: 2),
                ),
                child: const Center(
                  child: Icon(Icons.check_circle_rounded, color: AppConstants.primaryColor, size: 55),
                ),
              ),
              const SizedBox(height: 24),

              const Text(
                'Order Placed Successfully!',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppConstants.textPrimary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),

              Text(
                'Thank you $customerName! Your grocery order has been received by our store in Tando Adam.',
                style: const TextStyle(fontSize: 13, color: AppConstants.textSecondary, height: 1.4),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),

              // Order Details Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppConstants.scaffoldBg,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppConstants.borderSubtle),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Order Reference', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                        Text(orderRef, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: AppConstants.primaryColor)),
                      ],
                    ),
                    const Divider(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Total Amount to Pay', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                        Text('Rs. ${totalAmount.toStringAsFixed(0)}', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const Divider(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: const [
                        Text('Payment Method', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                        Text('Cash on Delivery (COD)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // WhatsApp Notification Button
              SizedBox(
                width: double.infinity,
                height: 48,
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF16A34A),
                    side: const BorderSide(color: Color(0xFF16A34A), width: 1.5),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.chat_bubble_outline, size: 18),
                  label: const Text('Send Order to WhatsApp', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  onPressed: _sendWhatsAppConfirmation,
                ),
              ),
              const SizedBox(height: 12),

              // Track Order Button
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppConstants.primaryColor,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    elevation: 0,
                  ),
                  icon: const Icon(Icons.delivery_dining, size: 20),
                  label: const Text('Track Order Live', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  onPressed: () {
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(
                        builder: (_) => OrderTrackingScreen(orderId: orderId),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(height: 12),

              // Back to Home Button
              TextButton(
                onPressed: () {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const MainScreen()),
                    (route) => false,
                  );
                },
                child: const Text('Continue Shopping →', style: TextStyle(color: AppConstants.textSecondary, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
