export const getProductImageUrl = (imagePath: string | null | undefined): string => {
  if (!imagePath) return '/assets/images/placeholder.svg';
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath;
  }
  // If it's a local static asset like a category or default badge
  if (imagePath.startsWith('/') && !imagePath.startsWith('/assets/images/products/')) {
    return imagePath;
  }
  // For PHP uploaded product images residing on the Hostinger server
  const cleanPath = imagePath.startsWith('/') ? imagePath.slice(1) : imagePath;
  return `http://216.198.79.1/${cleanPath}`;
};
