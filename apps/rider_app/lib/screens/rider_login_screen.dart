import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/rider_constants.dart';
import '../providers/rider_auth_provider.dart';
import 'rider_main_screen.dart';

class RiderLoginScreen extends StatefulWidget {
  const RiderLoginScreen({super.key});

  @override
  State<RiderLoginScreen> createState() => _RiderLoginScreenState();
}

class _RiderLoginScreenState extends State<RiderLoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _nameCtrl = TextEditingController(text: 'Muhammad Usman');
  final TextEditingController _phoneCtrl = TextEditingController(text: '03033943814');
  final TextEditingController _bikeCtrl = TextEditingController(text: 'ADM-1428');
  bool _isLoading = false;

  void _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    await context.read<RiderAuthProvider>().login(
      name: _nameCtrl.text.trim(),
      phone: _phoneCtrl.text.trim(),
      bike: _bikeCtrl.text.trim(),
    );

    setState(() => _isLoading = false);

    if (mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const RiderMainScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // App Icon & Header
                  Center(
                    child: Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        color: RiderConstants.primary,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: RiderConstants.primary.withOpacity(0.3),
                            blurRadius: 15,
                            offset: const Offset(0, 8),
                          ),
                        ],
                      ),
                      child: const Center(
                        child: Icon(Icons.two_wheeler_rounded, color: Colors.white, size: 45),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  const Text(
                    'HR TRADERS',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                      color: RiderConstants.textPrimary,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 4),

                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: RiderConstants.primary.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Text(
                      '🛵 RIDER DELIVERY DESK',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: RiderConstants.primaryDark,
                        letterSpacing: 1,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  const SizedBox(height: 8),

                  const Text(
                    'Deliver fresh groceries across Tando Adam and collect cash with live GPS maps.',
                    style: TextStyle(fontSize: 12, color: RiderConstants.textSecondary),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 32),

                  // Rider Name
                  TextFormField(
                    controller: _nameCtrl,
                    decoration: InputDecoration(
                      labelText: 'Rider Full Name',
                      prefixIcon: const Icon(Icons.person_outline),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    ),
                    validator: (v) => v == null || v.trim().isEmpty ? 'Please enter your name' : null,
                  ),
                  const SizedBox(height: 16),

                  // Rider Phone
                  TextFormField(
                    controller: _phoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelText: 'Phone Number (e.g. 03033943814)',
                      prefixIcon: const Icon(Icons.phone_outlined),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    ),
                    validator: (v) => v == null || v.trim().isEmpty ? 'Please enter your phone number' : null,
                  ),
                  const SizedBox(height: 16),

                  // Motorcycle Number
                  TextFormField(
                    controller: _bikeCtrl,
                    decoration: InputDecoration(
                      labelText: 'Motorcycle Reg. No. (e.g. ADM-1428)',
                      prefixIcon: const Icon(Icons.delivery_dining_outlined),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    ),
                    validator: (v) => v == null || v.trim().isEmpty ? 'Please enter bike number' : null,
                  ),
                  const SizedBox(height: 24),

                  // Start Duty Button
                  SizedBox(
                    height: 52,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: RiderConstants.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      onPressed: _isLoading ? null : _handleLogin,
                      child: _isLoading
                          ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text(
                              'Start Duty & View Deliveries ➔',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Store Helpline note
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: const [
                      Icon(Icons.store_mall_directory_outlined, size: 14, color: RiderConstants.textSecondary),
                      SizedBox(width: 6),
                      Text(
                        'HR Traders, Toor Colony, Tando Adam',
                        style: TextStyle(fontSize: 11, color: RiderConstants.textSecondary),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
