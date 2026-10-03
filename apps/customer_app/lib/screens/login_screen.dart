import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_constants.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Login Form
  final _loginFormKey = GlobalKey<FormState>();
  final TextEditingController _loginPhoneCtrl = TextEditingController();
  final TextEditingController _loginPassCtrl = TextEditingController();

  // Register Form
  final _regFormKey = GlobalKey<FormState>();
  final TextEditingController _regNameCtrl = TextEditingController();
  final TextEditingController _regPhoneCtrl = TextEditingController();
  final TextEditingController _regAddressCtrl = TextEditingController();
  final TextEditingController _regPassCtrl = TextEditingController();

  bool _obscureLogin = true;
  bool _obscureReg = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_loginFormKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();
    final res = await auth.login(
      _loginPhoneCtrl.text.trim(),
      _loginPassCtrl.text.trim(),
    );

    if (res['success'] == true) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Logged in successfully!'),
            backgroundColor: AppConstants.primaryDark,
          ),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Login failed. Please check credentials.'),
            backgroundColor: Colors.red.shade700,
          ),
        );
      }
    }
  }

  Future<void> _handleRegister() async {
    if (!_regFormKey.currentState!.validate()) return;

    final auth = context.read<AuthProvider>();
    final res = await auth.register(
      name: _regNameCtrl.text.trim(),
      phone: _regPhoneCtrl.text.trim(),
      address: _regAddressCtrl.text.trim(),
      password: _regPassCtrl.text.trim(),
    );

    if (res['success'] == true) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Account created and logged in!'),
            backgroundColor: AppConstants.primaryDark,
          ),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Registration failed.'),
            backgroundColor: Colors.red.shade700,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Customer Account', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 16)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.amber,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
          tabs: const [
            Tab(text: 'Login'),
            Tab(text: 'Register'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // ----------------------------------------------------
          // LOGIN TAB
          // ----------------------------------------------------
          SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _loginFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 10),
                  const Text('Welcome Back', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppConstants.textPrimary)),
                  const SizedBox(height: 4),
                  const Text('Login to access your orders and saved address', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                  const SizedBox(height: 24),

                  // Phone
                  TextFormField(
                    controller: _loginPhoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Mobile Phone Number',
                      hintText: 'e.g. 03033943814',
                      prefixIcon: Icon(Icons.phone_outlined, size: 18),
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.trim().isEmpty ? 'Phone is required' : null,
                  ),
                  const SizedBox(height: 16),

                  // Password
                  TextFormField(
                    controller: _loginPassCtrl,
                    obscureText: _obscureLogin,
                    decoration: InputDecoration(
                      labelText: 'Password',
                      prefixIcon: const Icon(Icons.lock_outline, size: 18),
                      suffixIcon: IconButton(
                        icon: Icon(_obscureLogin ? Icons.visibility_off : Icons.visibility, size: 18),
                        onPressed: () => setState(() => _obscureLogin = !_obscureLogin),
                      ),
                      border: const OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.trim().isEmpty ? 'Password is required' : null,
                  ),
                  const SizedBox(height: 24),

                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppConstants.primaryColor,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 0,
                      ),
                      onPressed: auth.isLoading ? null : _handleLogin,
                      child: auth.isLoading
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Login to Account', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ----------------------------------------------------
          // REGISTER TAB
          // ----------------------------------------------------
          SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _regFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 10),
                  const Text('Create Account', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppConstants.textPrimary)),
                  const SizedBox(height: 4),
                  const Text('Join HR Traders for faster delivery and order tracking', style: TextStyle(fontSize: 12, color: AppConstants.textSecondary)),
                  const SizedBox(height: 20),

                  // Name
                  TextFormField(
                    controller: _regNameCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Full Name *',
                      prefixIcon: Icon(Icons.person_outline, size: 18),
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.trim().isEmpty ? 'Name is required' : null,
                  ),
                  const SizedBox(height: 14),

                  // Phone
                  TextFormField(
                    controller: _regPhoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Mobile Phone Number *',
                      hintText: 'e.g. 03033943814',
                      prefixIcon: Icon(Icons.phone_outlined, size: 18),
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.trim().isEmpty ? 'Phone is required' : null,
                  ),
                  const SizedBox(height: 14),

                  // Address
                  TextFormField(
                    controller: _regAddressCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Delivery Address in Tando Adam *',
                      hintText: 'Colony name, street, nearby landmark',
                      prefixIcon: Icon(Icons.home_outlined, size: 18),
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.trim().isEmpty ? 'Address is required' : null,
                  ),
                  const SizedBox(height: 14),

                  // Password
                  TextFormField(
                    controller: _regPassCtrl,
                    obscureText: _obscureReg,
                    decoration: InputDecoration(
                      labelText: 'Create Password *',
                      prefixIcon: const Icon(Icons.lock_outline, size: 18),
                      suffixIcon: IconButton(
                        icon: Icon(_obscureReg ? Icons.visibility_off : Icons.visibility, size: 18),
                        onPressed: () => setState(() => _obscureReg = !_obscureReg),
                      ),
                      border: const OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.length < 6 ? 'Password must be at least 6 characters' : null,
                  ),
                  const SizedBox(height: 24),

                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppConstants.primaryColor,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 0,
                      ),
                      onPressed: auth.isLoading ? null : _handleRegister,
                      child: auth.isLoading
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Create My Account', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
