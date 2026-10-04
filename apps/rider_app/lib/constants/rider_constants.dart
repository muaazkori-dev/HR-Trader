import 'package:flutter/material.dart';

class RiderConstants {
  // App Branding
  static const String appName = 'HR Traders Rider';
  static const String appTagline = 'Fast Grocery Delivery Service';

  // API Endpoints
  static const String baseUrl = 'https://thehrtraders.com/api/v1';
  static const String supabaseUrl = 'https://xarwwlbbaevclyljkvzt.supabase.co/rest/v1';
  static const String supabaseKey = 'sb_publishable_v3-WUAhugqCtckYHXxcQNg_H-mBrrC4';

  // Store Helpline
  static const String storePhone = '+923033943814';
  static const String storeWhatsApp = '923033943814';
  static const String storeAddress = 'Toor Colony, Front of Hira Public School, Tando Adam';

  // Brand Colors (Emerald & Slate Theme)
  static const Color primary = Color(0xFF047857);      // Emerald 700
  static const Color primaryDark = Color(0xFF065F46);  // Emerald 800
  static const Color primaryLight = Color(0xFF10B981); // Emerald 500
  static const Color accent = Color(0xFFF59E0B);        // Amber 500
  static const Color scaffoldBg = Color(0xFFF8FAFC);    // Slate 50
  static const Color cardBg = Colors.white;
  static const Color textPrimary = Color(0xFF0F172A);   // Slate 900
  static const Color textSecondary = Color(0xFF64748B); // Slate 500
  static const Color borderSubtle = Color(0xFFE2E8F0);  // Slate 200

  // Status Colors
  static const Color statusPending = Color(0xFFF59E0B);      // Amber
  static const Color statusPackaging = Color(0xFF3B82F6);    // Blue
  static const Color statusOut = Color(0xFF8B5CF6);          // Purple
  static const Color statusDelivered = Color(0xFF10B981);    // Emerald
  static const Color statusCancelled = Color(0xFFEF4444);    // Red
}
