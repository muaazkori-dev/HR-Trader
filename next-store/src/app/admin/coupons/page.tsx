import React from 'react';
import CouponsContent from '@/components/admin/CouponsContent';

export const revalidate = 0; // Disable static cache for real-time coupon updates

export default function AdminCouponsPage() {
  return <CouponsContent />;
}
