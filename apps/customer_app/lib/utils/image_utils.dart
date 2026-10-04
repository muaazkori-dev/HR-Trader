class ImageUtils {
  /// Converts raw high-resolution Supabase storage URLs into lightweight,
  /// CDN-compressed WebP thumbnails on the fly (saving 95% data and loading instantly).
  static String getOptimizedUrl(String? rawUrl, {int width = 280, int quality = 75}) {
    if (rawUrl == null || rawUrl.isEmpty || rawUrl == 'null' || rawUrl == 'undefined') {
      return '';
    }

    final trimmed = rawUrl.trim();

    // 1. Supabase Cloud Storage Object URL -> Convert to CDN Render endpoint
    if (trimmed.contains('/storage/v1/object/public/')) {
      final renderUrl = trimmed.replaceFirst(
        '/storage/v1/object/public/',
        '/storage/v1/render/image/public/',
      );
      final separator = renderUrl.contains('?') ? '&' : '?';
      return '$renderUrl${separator}width=$width&quality=$quality&resize=contain';
    }

    // 2. Already a Supabase Render URL
    if (trimmed.contains('/storage/v1/render/image/public/')) {
      if (!trimmed.contains('width=')) {
        final separator = trimmed.contains('?') ? '&' : '?';
        return '$trimmed${separator}width=$width&quality=$quality&resize=contain';
      }
      return trimmed;
    }

    // 3. Relative image filename stored in DB (e.g., "products/h6nr0lxy007.png" or "h6nr0lxy007.png")
    if (!trimmed.startsWith('http://') && !trimmed.startsWith('https://')) {
      final clean = trimmed.replaceAll(RegExp(r'^/+'), '');
      final filename = clean.split('/').last;
      if (filename.isEmpty || filename.contains('placeholder')) {
        return '';
      }
      return 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/render/image/public/product-images/products/$filename?width=$width&quality=$quality&resize=contain';
    }

    return trimmed;
  }
}
