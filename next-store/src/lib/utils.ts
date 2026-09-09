export const getProductImageUrl = (
  imagePath: string | null | undefined,
  options?: { width?: number; quality?: number }
): string => {
  if (!imagePath || imagePath === '' || imagePath === 'null' || imagePath === 'undefined') {
    return '/assets/images/placeholder.svg';
  }
  
  const trimmed = imagePath.trim();
  const width = options?.width || 400;
  const quality = options?.quality || 80;
  
  // If it's a Supabase Cloud Storage public object URL
  if (trimmed.includes('supabase.co/storage/v1/object/public/')) {
    const renderUrl = trimmed.replace(
      '/storage/v1/object/public/',
      '/storage/v1/render/image/public/'
    );
    return `${renderUrl}?width=${width}&quality=${quality}`;
  }
  
  // If it's already a render URL
  if (trimmed.includes('supabase.co/storage/v1/render/image/public/')) {
    if (!trimmed.includes('width=')) {
      return `${trimmed}?width=${width}&quality=${quality}`;
    }
    return trimmed;
  }
  
  // If it's another external HTTPS/HTTP URL (e.g. remote image)
  if (trimmed.startsWith('https://') || trimmed.startsWith('http://')) {
    if (trimmed.startsWith('http://xarwwlbbaevclyljkvzt.supabase.co')) {
      const secureUrl = trimmed.replace('http://', 'https://');
      if (secureUrl.includes('/storage/v1/object/public/')) {
        return secureUrl.replace('/storage/v1/object/public/', '/storage/v1/render/image/public/') + `?width=${width}&quality=${quality}`;
      }
      return secureUrl;
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
  
  // Fallback to Supabase Cloud Storage Render endpoint with automatic optimization
  return `https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/render/image/public/product-images/products/${filename}?width=${width}&quality=${quality}`;
};
