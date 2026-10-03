import 'package:flutter/material.dart';

class AppConstants {
  // Store Branding
  static const String appName = 'HR TRADERS';
  static const String appTagline = 'Fresh Groceries Delivered Fast';
  
  // Central API Endpoint
  static const String baseUrl = 'https://thehrtraders.com/api/v1';

  // Support Contacts
  static const String storePhone = '+923033943814';
  static const String storeWhatsApp = '923033943814';
  static const String storeEmail = 'info@hrtraders.com';
  static const String storeAddress = 'Toor Colony, Front of Hira Public School, Tando Adam';

  // Primary Theme Colors
  static const Color primaryColor = Color(0xFF047857); // Emerald 700
  static const Color primaryDark = Color(0xFF065F46);  // Emerald 800
  static const Color primaryLight = Color(0xFF10B981); // Emerald 500
  static const Color accentGold = Color(0xFFF59E0B);   // Amber 500
  static const Color scaffoldBg = Color(0xFFF8FAFC);   // Slate 50
  static const Color cardBg = Colors.white;
  static const Color textPrimary = Color(0xFF0F172A);  // Slate 900
  static const Color textSecondary = Color(0xFF64748B);// Slate 500
  static const Color borderSubtle = Color(0xFFE2E8F0); // Slate 200

  // Delivery Configuration
  static const double defaultShippingFee = 180.0;
  static const double freeShippingThreshold = 2500.0;
}
