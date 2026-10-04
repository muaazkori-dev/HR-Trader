import 'package:flutter/material.dart';

class AdminConstants {
  // Branding
  static const String appName = 'HR Traders Admin';
  static const String appTagline = 'Store Management & POS Command Center';

  // API Endpoints
  static const String baseUrl = 'https://thehrtraders.com/api/v1';
  static const String supabaseUrl = 'https://xarwwlbbaevclyljkvzt.supabase.co/rest/v1';
  static const String supabaseKey = 'sb_publishable_v3-WUAhugqCtckYHXxcQNg_H-mBrrC4';

  // Theme Colors (Slate 900 & Emerald Theme)
  static const Color primary = Color(0xFF047857);        // Emerald 700
  static const Color primaryDark = Color(0xFF065F46);    // Emerald 800
  static const Color primaryLight = Color(0xFF10B981);   // Emerald 500
  static const Color slateDark = Color(0xFF0F172A);      // Slate 900
  static const Color slateCard = Color(0xFF1E293B);      // Slate 800
  static const Color accent = Color(0xFFF59E0B);         // Amber 500
  static const Color scaffoldBg = Color(0xFFF1F5F9);     // Slate 100
  static const Color cardBg = Colors.white;
  static const Color textPrimary = Color(0xFF0F172A);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color borderSubtle = Color(0xFFE2E8F0);

  // Status Colors
  static const Color statusPending = Color(0xFFF59E0B);
  static const Color statusPackaging = Color(0xFF3B82F6);
  static const Color statusOut = Color(0xFF8B5CF6);
  static const Color statusDelivered = Color(0xFF10B981);
  static const Color statusCancelled = Color(0xFFEF4444);

  // Category Icons & Slugs
  static const Map<String, String> categoryLabels = {
    'anaj': 'Baking & Cooking',
    'ice_cream': 'Ice Creams',
    'beverages': 'Beverages & Cold Drinks',
    'milk': 'Dairy & Nutrition',
    'cosmetics': 'Cosmetics & Care',
    'confectionary': 'Snacks & Chips',
    'bakery': 'Bakery & Breakfast',
    'sauce': 'Sauces & Pastas',
    'stationary': 'Stationary',
    'household_and_laundry': 'Household & Laundry',
  };
}
