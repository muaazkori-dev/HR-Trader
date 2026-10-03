import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/app_constants.dart';
import '../providers/auth_provider.dart';
import 'login_screen.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  Future<void> _openUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: AppConstants.scaffoldBg,
      appBar: AppBar(
        title: const Text('My Account', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 17)),
        backgroundColor: AppConstants.primaryColor,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // User Header Card
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: AppConstants.primaryColor,
                    child: Text(
                      auth.isAuthenticated ? auth.userName.substring(0, 1).toUpperCase() : 'G',
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          auth.userName,
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppConstants.textPrimary),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          auth.isAuthenticated ? auth.userPhone : 'Guest Customer',
                          style: const TextStyle(fontSize: 12, color: AppConstants.textSecondary),
                        ),
                        if (auth.userAddress.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(
                            auth.userAddress,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 11, color: Colors.grey),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (!auth.isAuthenticated)
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppConstants.primaryColor,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const LoginScreen()),
                        );
                      },
                      child: const Text('Login'),
                    ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Store Info & Support Card
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.chat_bubble_outline, color: Color(0xFF16A34A)),
                    title: const Text('WhatsApp Customer Support', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    subtitle: const Text('Chat with store team directly', style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                    trailing: const Icon(Icons.chevron_right, size: 20),
                    onTap: () => _openUrl('https://wa.me/${AppConstants.storeWhatsApp}'),
                  ),
                  const Divider(height: 1, indent: 55),
                  ListTile(
                    leading: const Icon(Icons.phone_outlined, color: AppConstants.primaryColor),
                    title: const Text('Call Store Helpdesk', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    subtitle: const Text(AppConstants.storePhone, style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                    trailing: const Icon(Icons.chevron_right, size: 20),
                    onTap: () => _openUrl('tel:${AppConstants.storePhone}'),
                  ),
                  const Divider(height: 1, indent: 55),
                  ListTile(
                    leading: const Icon(Icons.storefront_outlined, color: Colors.orange),
                    title: const Text('Store Location', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    subtitle: const Text(AppConstants.storeAddress, style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Policies & Google Play Compliance Card
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.borderSubtle),
              ),
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.policy_outlined, color: Colors.indigo),
                    title: const Text('Privacy Policy', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    subtitle: const Text('Google Play Store compliance', style: TextStyle(fontSize: 11, color: AppConstants.textSecondary)),
                    trailing: const Icon(Icons.open_in_new, size: 18),
                    onTap: () => _openUrl('https://thehrtraders.com/privacy_policy.php'),
                  ),
                  const Divider(height: 1, indent: 55),
                  ListTile(
                    leading: const Icon(Icons.info_outline, color: Colors.blueGrey),
                    title: const Text('App Version', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                    trailing: const Text('1.0.0 (Release)', style: TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Logout Button (if logged in)
            if (auth.isAuthenticated)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.red.shade700,
                    side: BorderSide(color: Colors.red.shade300),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  icon: const Icon(Icons.logout, size: 18),
                  label: const Text('Logout Account', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () {
                    showDialog(
                      context: context,
                      builder: (_) => AlertDialog(
                        title: const Text('Logout?'),
                        content: const Text('Are you sure you want to log out of your account?'),
                        actions: [
                          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                          TextButton(
                            onPressed: () {
                              auth.logout();
                              Navigator.pop(context);
                            },
                            child: const Text('Logout', style: TextStyle(color: Colors.red)),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}
