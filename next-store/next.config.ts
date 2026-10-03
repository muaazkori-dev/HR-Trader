import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    unoptimized: true,
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'xarwwlbbaevclyljkvzt.supabase.co',
        port: '',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: '**',
      },
      {
        protocol: 'http',
        hostname: '**',
      },
    ],
  },
  async redirects() {
    return [
      {
        source: '/privacy_policy.php',
        destination: '/privacy-policy',
        permanent: true,
      },
      {
        source: '/privacy-policy.php',
        destination: '/privacy-policy',
        permanent: true,
      },
      {
        source: '/privacy_policy',
        destination: '/privacy-policy',
        permanent: true,
      },
    ];
  },
};

export default nextConfig;
