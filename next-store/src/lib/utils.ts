export const getProductImageUrl = (imagePath: string | null | undefined): string => {
  if (!imagePath || imagePath === '' || imagePath === 'null' || imagePath === 'undefined') {
    return '/assets/images/placeholder.svg';
  }
  
  const trimmed = imagePath.trim();
  
  // If it's already an external HTTPS/HTTP URL (e.g. direct Supabase CDN or remote image)
  if (trimmed.startsWith('https://') || trimmed.startsWith('http://')) {
    if (trimmed.startsWith('http://xarwwlbbaevclyljkvzt.supabase.co')) {
      return trimmed.replace('http://', 'https://');
    }
    return trimmed;
  }
  
  // If it's a static SVG / PNG asset in public/
  if (trimmed.startsWith('/') && !trimmed.startsWith('/assets/images/products/')) {
    return trimmed;
  }
  
  // For relative paths like "assets/images/products/foo.png" or "foo.png"
  const cleanPath = trimmed.replace(/^\/+/, '');
  const filename = cleanPath.split('/').pop() || cleanPath;
  
  if (!filename || filename === 'placeholder.svg' || filename === 'placeholder.jpg') {
    return '/assets/images/placeholder.svg';
  }
  
  // Fallback to Supabase Cloud Storage CDN
  return `https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/products/${filename}`;
};
