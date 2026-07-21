import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'xarwwlbbaevclyljkvzt.supabase.co',
        port: '',
        pathname: '/**',
      },
      {
        protocol: 'http',
        hostname: '216.198.79.1',
        port: '',
        pathname: '/**',
      },
    ],
  },
};

export default nextConfig;
