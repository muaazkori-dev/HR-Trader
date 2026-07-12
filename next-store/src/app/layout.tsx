import type { Metadata } from 'next';
import { Outfit, Noto_Nastaliq_Urdu } from 'next/font/google';
import './globals.css';
import { CartProvider } from '@/context/CartContext';
import { AuthProvider } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { BottomNav } from '@/components/BottomNav';


const outfit = Outfit({
  subsets: ['latin'],
  variable: '--font-sans',
  display: 'swap',
});

const urdu = Noto_Nastaliq_Urdu({
  subsets: ['arabic'],
  variable: '--font-urdu',
  weight: ['400', '700'],
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'HR Traders - Premium Online Grocery & Cosmetics Store',
  description:
    'Shop the freshest organic grains, cosmetics, and cold beverages online. Fast home delivery and retail store in Tando Adam.',
};

export const revalidate = 30; // Enable 30-second edge CDN caching for instant page transitions

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  let activeTheme = 'emerald_green';
  
  try {
    const { data } = await supabase
      .from('settings')
      .select('val_value')
      .eq('key_name', 'active_theme')
      .single();
    if (data?.val_value) {
      activeTheme = data.val_value;
    }
  } catch (error) {
    console.error('Failed to load active theme:', error);
  }

  return (
    <html
      lang="en"
      className={`${outfit.variable} ${urdu.variable} h-full antialiased`}
    >
      <body className={`theme-${activeTheme} min-h-full flex flex-col transition-colors duration-300 pb-16 md:pb-0`}>
        <AuthProvider>
          <CartProvider>
            {children}
            <BottomNav />
          </CartProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
