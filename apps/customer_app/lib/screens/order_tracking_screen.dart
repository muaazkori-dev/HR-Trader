import 'dart:async';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/app_constants.dart';
import '../models/order_model.dart';
import '../services/api_service.dart';

class OrderTrackingScreen extends StatefulWidget {
  final int orderId;
  const OrderTrackingScreen({super.key, required this.orderId});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  bool _isLoading = true;
  OrderDetail? _order;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _fetchTracking();
    // Auto-refresh order status every 15 seconds for live tracking updates
    _timer = Timer.periodic(const Duration(seconds: 15), (_) => _fetchTracking(silent: true));
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _fetchTracking({bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    final detail = await ApiService.trackOrder(widget.orderId);
    if (mounted) {
      setState(() {
        _order = detail;
        _isLoading = false;
      });
    }
  }

  Future<void> _callRider(String phone) async {
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(backgroundColor: AppConstants.primaryColor, elevation: 0),
        body: const Center(child: CircularProgressIndicator(color: AppConstants.primaryColor)),
      );
    }

    if (_order == null) {
      return Scaffold(
        appBar: AppBar(backgroundColor: AppConstants.primaryColor, elevation: 0),
        body: const Center(child: Text('Order not found or tracking unavailable.')),
      );
    }

    final o = _order!;

    return Scaffold(
      backgroundColor: AppConstants.scaffoldBg,
      appBar: AppBar(
        title: Text(o.orderRef, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: () => _fetchTracking(),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status Header Banner
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                children: [
                  Text(
                    o.stepInfo['label'] ?? 'Order Active',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppConstants.primaryColor),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    o.stepInfo['description'] ?? 'Your groceries are being prepared in Tando Adam.',
                    style: const TextStyle(fontSize: 12, color: AppConstants.textSecondary),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Stepper
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Delivery Progress', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  _buildStep(
                    stepNumber: 1,
                    title: 'Order Confirmed',
                    subtitle: 'Store received your grocery order',
                    currentStep: o.stepInfo['step'] ?? 1,
                    isLast: false,
                  ),
                  _buildStep(
                    stepNumber: 2,
                    title: 'Packing Groceries',
                    subtitle: 'Items are being checked and bagged',
                    currentStep: o.stepInfo['step'] ?? 1,
                    isLast: false,
                  ),
                  _buildStep(
                    stepNumber: 3,
                    title: 'Out for Delivery',
                    subtitle: 'Rider is on the way to your address',
                    currentStep: o.stepInfo['step'] ?? 1,
                    isLast: false,
                  ),
                  _buildStep(
                    stepNumber: 4,
                    title: 'Delivered',
                    subtitle: 'Package handed over and payment received',
                    currentStep: o.stepInfo['step'] ?? 1,
                    isLast: true,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Assigned Rider Card (if assigned)
            if (o.rider != null) ...[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFECFDF5),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFA7F3D0)),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: AppConstants.primaryColor,
                      radius: 24,
                      child: const Icon(Icons.delivery_dining, color: Colors.white, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Assigned Delivery Rider', style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                          Text(o.rider!.name, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppConstants.textPrimary)),
                          Text(o.rider!.phone, style: const TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.phone, color: AppConstants.primaryColor),
                      onPressed: () => _callRider(o.rider!.phone),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Delivery Details
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Delivery Destination', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Text(o.customerName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  Text(o.customerPhone, style: const TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                  const SizedBox(height: 4),
                  Text(o.customerAddress, style: const TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                  if (o.deliveryLat != null) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: const [
                        Icon(Icons.check_circle, color: AppConstants.primaryColor, size: 14),
                        SizedBox(width: 4),
                        Text('Live GPS Location Attached', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppConstants.primaryDark)),
                      ],
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Items Summary
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Ordered Items', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  ...o.items.map((item) => Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('${item.quantity}x ${item.name}', style: const TextStyle(fontSize: 12)),
                            Text('Rs. ${item.lineTotal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      )),
                  const Divider(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Total Cash to Pay (COD)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w900)),
                      Text('Rs. ${o.totalAmount.toStringAsFixed(0)}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: AppConstants.primaryColor)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStep({
    required int stepNumber,
    required String title,
    required String subtitle,
    required int currentStep,
    required bool isLast,
  }) {
    final bool isCompleted = currentStep >= stepNumber;
    final bool isCurrent = currentStep == stepNumber;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: isCompleted ? AppConstants.primaryColor : Colors.grey.shade200,
                shape: BoxShape.circle,
              ),
              child: Center(
                child: isCompleted
                    ? const Icon(Icons.check, color: Colors.white, size: 16)
                    : Text('$stepNumber', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
              ),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 35,
                color: isCompleted && currentStep > stepNumber ? AppConstants.primaryColor : Colors.grey.shade200,
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: isCurrent ? AppConstants.primaryColor : (isCompleted ? AppConstants.textPrimary : Colors.grey),
                ),
              ),
              Text(
                subtitle,
                style: TextStyle(fontSize: 11, color: isCompleted ? AppConstants.textSecondary : Colors.grey.shade400),
              ),
              const SizedBox(height: 18),
            ],
          ),
        ),
      ],
    );
  }
}
