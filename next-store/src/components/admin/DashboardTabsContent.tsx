'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { useRouter } from 'next/navigation';
import { 
  TrendingUp, 
  TrendingDown, 
  Coins, 
  ClipboardList, 
  ArrowRight, 
  AlertTriangle,
  UserPlus,
  Trash2,
  ShieldCheck,
  Lock,
  Unlock,
  Phone,
  MapPin,
  Mail,
  Printer,
  Palette,
  Info,
  Save,
  CheckCircle2,
  Folder,
  Layers,
  Plus,
  Edit2,
  X,
  Upload,
  Sparkles,
  Link as LinkIcon,
  Image as ImageIcon
} from 'lucide-react';
import Link from 'next/link';

interface Product {
  id: number;
  barcode: string;
  name: string;
  description?: string;
  price: number;
  stock_quantity: number;
  unit: string;
  weight?: string;
  category: string;
  image: string;
}

interface SettingItem {
  key_name: string;
  val_value: string;
}

interface StaffProfile {
  id: string;
  name: string;
  phone?: string;
  address?: string;
  role: string;
  created_at: string;
  username?: string;
}

interface ProductDemand {
  id: number;
  customer_name: string;
  customer_phone: string;
  demand_details: string;
  status: 'pending' | 'confirmed';
  created_at: string;
}

interface Category {
  id: string;
  name: string;
  urdu: string;
  image: string;
}

interface Banner {
  id: number;
  tag: string;
  title: string;
  desc: string;
  link: string;
  image: string | null;
  theme: string;
}

interface DashboardTabsContentProps {
  initialStats: {
    grossSales: number;
    netExpense: number;
    netProfit: number;
    activeFulfillments: number;
    posSales: number;
    onlineSales: number;
    posProfit: number;
    onlineProfit: number;
  };
  initialProducts: Product[];
  initialSettings: SettingItem[];
  initialStaffList: StaffProfile[];
  initialDemands: ProductDemand[];
}

const THEME_OPTIONS = [
  { id: 'emerald_green', label: 'Emerald Green (Light Default)', bg: 'bg-emerald-500' },
  { id: 'rose_gold', label: 'Rose Gold (Light Elegant)', bg: 'bg-pink-500' },
  { id: 'slate_blue', label: 'Slate Blue (Light Soft)', bg: 'bg-blue-500' },
  { id: 'amber_honey', label: 'Amber Honey (Light Sunny)', bg: 'bg-amber-500' },
  { id: 'classic_light', label: 'Monochrome (Light High Contrast)', bg: 'bg-zinc-800' },
  { id: 'midnight_indigo', label: 'Midnight Indigo (Dark Mode)', bg: 'bg-indigo-600' },
  { id: 'cyberpunk_neon', label: 'Cyberpunk Neon (Dark Neon)', bg: 'bg-cyan-500' },
  { id: 'deep_purple', label: 'Deep Purple (Dark Royal)', bg: 'bg-purple-600' },
  { id: 'forest_dark', label: 'Forest Dark (Dark Nature)', bg: 'bg-emerald-800' },
  { id: 'crimson_dark', label: 'Crimson Ruby (Dark Intense)', bg: 'bg-rose-600' },
];

export const DashboardTabsContent: React.FC<DashboardTabsContentProps> = ({
  initialStats,
  initialProducts,
  initialSettings,
  initialStaffList,
  initialDemands
}) => {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState<'overview' | 'planner' | 'settings'>('overview');

  // Stats
  const [stats, setStats] = useState(initialStats);
  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [demands, setDemands] = useState<ProductDemand[]>(initialDemands);

  // Settings State Managers
  const getValue = (key: string, fallback = '') => {
    return initialSettings.find((s) => s.key_name === key)?.val_value || fallback;
  };

  const [activeTheme, setActiveTheme] = useState(getValue('active_theme', 'emerald_green'));
  const [shopStatus, setShopStatus] = useState(getValue('shop_status', 'open'));
  const [lowStock, setLowStock] = useState(getValue('low_stock_threshold', '5'));
  const [announcement, setAnnouncement] = useState(getValue('homepage_announcement', 'Welcome to HR Traders!'));
  const [dispatchTemplate, setDispatchTemplate] = useState(getValue('whatsapp_dispatch_template', ''));
  const [storeName, setStoreName] = useState(getValue('store_name', 'HR Traders'));
  const [storePhone, setStorePhone] = useState(getValue('store_phone', '+92 333 7155323'));
  const [storeEmail, setStoreEmail] = useState(getValue('store_email', 'owner@hrtraders.com'));
  const [storeAddress, setStoreAddress] = useState(getValue('store_address', 'Main Bazaar, Lahore, Pakistan'));
  const [storeMapsUrl, setStoreMapsUrl] = useState(getValue('store_maps_url', ''));
  const [whatsappNumber, setWhatsappNumber] = useState(getValue('whatsapp_number', '03337155323'));
  const [facebookUrl, setFacebookUrl] = useState(getValue('facebook_url', ''));
  const [instagramUrl, setInstagramUrl] = useState(getValue('instagram_url', ''));
  const [tiktokUrl, setTiktokUrl] = useState(getValue('tiktok_url', ''));
  const [minOrderValue, setMinOrderValue] = useState(getValue('min_order_value', '0'));
  const [shippingFee, setShippingFee] = useState(getValue('shipping_fee', '0'));
  const [storeCurrency, setStoreCurrency] = useState(getValue('store_currency', 'Rs.'));

  const [branch1Address, setBranch1Address] = useState(getValue('branch_1_address', 'Toor Colony, Front of Hira Public School, Tando Adam'));
  const [branch1Phone, setBranch1Phone] = useState(getValue('branch_1_phone', '+92 303 3943814'));
  const [branch1MapsUrl, setBranch1MapsUrl] = useState(getValue('branch_1_maps_url', ''));
  const [branch2Address, setBranch2Address] = useState(getValue('branch_2_address', 'Gulshan-e-Sardar, near Ayoub Hotel, Tando Adam'));
  const [branch2Phone, setBranch2Phone] = useState(getValue('branch_2_phone', '+92 313 7889859'));
  const [branch2MapsUrl, setBranch2MapsUrl] = useState(getValue('branch_2_maps_url', ''));

  const [timingsSatThu, setTimingsSatThu] = useState(getValue('timings_sat_thu', '6:00 AM - 12:00 PM'));
  const [timingsFri, setTimingsFri] = useState(getValue('timings_fri', '6:00 AM - 12:00 PM'));
  const [timingsFriEve, setTimingsFriEve] = useState(getValue('timings_fri_eve', '4:00 PM - 12:00 AM'));

  const [promoAdEnabled, setPromoAdEnabled] = useState(getValue('promo_ad_enabled', 'false'));
  const [promoAdImage, setPromoAdImage] = useState(getValue('promo_ad_image', ''));
  const [promoAdLink, setPromoAdLink] = useState(getValue('promo_ad_link', ''));
  const [promoAdFile, setPromoAdFile] = useState<File | null>(null);
  const [promoAdPreview, setPromoAdPreview] = useState('');

  // Google OAuth Settings state
  const [googleClientId, setGoogleClientId] = useState(getValue('google_client_id', ''));
  const [googleClientSecret, setGoogleClientSecret] = useState(getValue('google_client_secret', ''));

  const [submittingSettings, setSubmittingSettings] = useState(false);
  const [settingsMessage, setSettingsMessage] = useState('');
  const [isSettingsError, setIsSettingsError] = useState(false);

  // Staff States
  const [staffList, setStaffList] = useState<StaffProfile[]>(initialStaffList);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [staffPhone, setStaffPhone] = useState('');
  const [staffAddress, setStaffAddress] = useState('');
  const [isSubmittingStaff, setIsSubmittingStaff] = useState(false);
  const [staffFormError, setStaffFormError] = useState('');
  const [staffFormSuccess, setStaffFormSuccess] = useState('');

  // Categories Manager States
  const [categories, setCategories] = useState<Category[]>([]);
  const [loadingCategories, setLoadingCategories] = useState(false);
  const [newCatName, setNewCatName] = useState('');
  const [newCatUrdu, setNewCatUrdu] = useState('');
  const [newCatFile, setNewCatFile] = useState<File | null>(null);
  const [newCatPreview, setNewCatPreview] = useState('');
  const [editingCatId, setEditingCatId] = useState<string | null>(null);
  const [editCatName, setEditCatName] = useState('');
  const [editCatUrdu, setEditCatUrdu] = useState('');
  const [editCatFile, setEditCatFile] = useState<File | null>(null);
  const [editCatPreview, setEditCatPreview] = useState('');

  // Banners Manager States
  const [banners, setBanners] = useState<Banner[]>([]);
  const [newBannerTag, setNewBannerTag] = useState('');
  const [newBannerTitle, setNewBannerTitle] = useState('');
  const [newBannerDesc, setNewBannerDesc] = useState('');
  const [newBannerLink, setNewBannerLink] = useState('/shop');
  const [newBannerTheme, setNewBannerTheme] = useState('emerald');
  const [newBannerFile, setNewBannerFile] = useState<File | null>(null);
  const [newBannerPreview, setNewBannerPreview] = useState('');
  const [editingBannerId, setEditingBannerId] = useState<number | null>(null);
  const [editBannerTag, setEditBannerTag] = useState('');
  const [editBannerTitle, setEditBannerTitle] = useState('');
  const [editBannerDesc, setEditBannerDesc] = useState('');
  const [editBannerLink, setEditBannerLink] = useState('');
  const [editBannerTheme, setEditBannerTheme] = useState('emerald');
  const [editBannerFile, setEditBannerFile] = useState<File | null>(null);
  const [editBannerPreview, setEditBannerPreview] = useState('');

  // Load categories & banners from settings
  useEffect(() => {
    const rawCategories = getValue('store_categories');
    if (rawCategories) {
      try {
        setCategories(JSON.parse(rawCategories));
      } catch (e) {
        console.error(e);
      }
    }
    const rawBanners = getValue('store_hero_banners');
    if (rawBanners) {
      try {
        setBanners(JSON.parse(rawBanners));
      } catch (e) {
        console.error(e);
      }
    }
  }, [initialSettings]);

  // Save Settings wrapper
  const handleSaveSettingsGroup = async (updates: { key_name: string; val_value: string }[]) => {
    setSubmittingSettings(true);
    setSettingsMessage('');
    setIsSettingsError(false);

    try {
      for (const update of updates) {
        const { error } = await supabase
          .from('settings')
          .upsert(update, { onConflict: 'key_name' });
        if (error) throw error;
      }
      setSettingsMessage('Settings saved successfully!');
      setIsSettingsError(false);
      
      // reload or refresh
      router.refresh();
    } catch (err: any) {
      setIsSettingsError(true);
      setSettingsMessage(err.message || 'Failed to save settings to database.');
    } finally {
      setSubmittingSettings(false);
    }
  };

  // Upload asset file generic
  const uploadAssetFile = async (file: File, folder: string): Promise<string> => {
    const fileExt = file.name.split('.').pop();
    const fileName = `${folder}_${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
    const filePath = `${folder}/${fileName}`;

    const { error: uploadError } = await supabase.storage
      .from('product-images')
      .upload(filePath, file, { cacheControl: '3600', upsert: true });

    if (uploadError) throw uploadError;

    const { data } = supabase.storage
      .from('product-images')
      .getPublicUrl(filePath);

    return data.publicUrl;
  };

  // Staff Account register
  const handleRegisterStaff = async (e: React.FormEvent) => {
    e.preventDefault();
    setStaffFormError('');
    setStaffFormSuccess('');

    if (!email.trim() || !password.trim() || !fullName.trim()) {
      setStaffFormError('Email, Password, and Full Name are required.');
      return;
    }

    setIsSubmittingStaff(true);
    try {
      const { data: authData, error: authErr } = await supabase.auth.signUp({
        email: email.trim(),
        password: password.trim()
      });

      if (authErr) throw authErr;
      if (!authData.user) throw new Error('Registration failed.');

      const { error: profileErr } = await supabase
        .from('profiles')
        .insert({
          id: authData.user.id,
          username: email.split('@')[0],
          name: fullName.trim(),
          phone: staffPhone.trim(),
          address: staffAddress.trim(),
          role: 'manager'
        });

      if (profileErr) throw profileErr;

      setStaffFormSuccess(`Staff manager registered successfully!`);
      setEmail('');
      setPassword('');
      setFullName('');
      setStaffPhone('');
      setStaffAddress('');

      // Refresh list
      const { data } = await supabase
        .from('profiles')
        .select('*')
        .in('role', ['owner', 'manager'])
        .order('name', { ascending: true });
      if (data) setStaffList(data as StaffProfile[]);

    } catch (err: any) {
      setStaffFormError(err.message || 'Failed to create account.');
    } finally {
      setIsSubmittingStaff(false);
    }
  };

  // Revoke Staff
  const handleRemoveStaff = async (id: string, name: string) => {
    if (!confirm(`Are you sure you want to revoke permissions for '${name}'?`)) return;

    try {
      const { error } = await supabase
        .from('profiles')
        .update({ role: 'customer' })
        .eq('id', id);

      if (error) throw error;
      alert(`Privileges revoked successfully.`);

      // Refresh list
      const { data } = await supabase
        .from('profiles')
        .select('*')
        .in('role', ['owner', 'manager'])
        .order('name', { ascending: true });
      if (data) setStaffList(data as StaffProfile[]);
    } catch (err: any) {
      alert(err.message || 'Failed.');
    }
  };

  // Restock Sheet Print
  const handlePrintRestock = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const lowStockItems = products.filter(p => p.stock_quantity <= parseInt(lowStock));
    
    let html = `
      <html>
        <head>
          <title>HR Traders - Restocking Sheet</title>
          <style>
            body { font-family: sans-serif; padding: 20px; color: #333; }
            h1 { text-align: center; font-size: 20px; margin-bottom: 5px; }
            p { text-align: center; font-size: 12px; color: #666; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .badge { color: red; font-weight: bold; }
          </style>
        </head>
        <body onload="window.print();window.close();">
          <h1>HR Traders - Inventory Restock Sheet</h1>
          <p>Generated on ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()} (Stock Threshold: ${lowStock})</p>
          <table>
            <thead>
              <tr>
                <th>Barcode</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Current Stock</th>
                <th>Suggested Order</th>
              </tr>
            </thead>
            <tbody>
              ${lowStockItems.map(item => `
                <tr>
                  <td>${item.barcode || '—'}</td>
                  <td>${item.name} (${item.weight || ''} ${item.unit})</td>
                  <td>${item.category.toUpperCase()}</td>
                  <td class="badge">${item.stock_quantity} left</td>
                  <td>+${50 - item.stock_quantity} units</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </body>
      </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();
  };

  // Category Manager save list
  const handleSaveCategoriesList = async (updated: Category[]) => {
    try {
      await supabase
        .from('settings')
        .upsert({
          key_name: 'store_categories',
          val_value: JSON.stringify(updated)
        }, { onConflict: 'key_name' });
      setCategories(updated);
    } catch (e) {
      console.error(e);
    }
  };

  // Add Category Handler
  const handleAddCategory = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCatName.trim()) return;

    try {
      let key = newCatName.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_');
      if (categories.some(c => c.id === key)) {
        key = key + '_' + Date.now().toString().slice(-4);
      }

      let imageUrl = `/assets/images/categories/${key}.png`;
      if (newCatFile) {
        imageUrl = await uploadAssetFile(newCatFile, 'categories');
      }

      const updated = [...categories, {
        id: key,
        name: newCatName.trim(),
        urdu: newCatUrdu.trim(),
        image: imageUrl
      }];

      await handleSaveCategoriesList(updated);
      setNewCatName('');
      setNewCatUrdu('');
      setNewCatFile(null);
      setNewCatPreview('');
      alert('Category added successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed');
    }
  };

  // Edit Category Save
  const handleSaveCategoryEdit = async (id: string) => {
    try {
      let finalImg = editCatPreview;
      if (editCatFile) {
        finalImg = await uploadAssetFile(editCatFile, 'categories');
      }

      const updated = categories.map(c => {
        if (c.id === id) {
          return { ...c, name: editCatName.trim(), urdu: editCatUrdu.trim(), image: finalImg };
        }
        return c;
      });

      await handleSaveCategoriesList(updated);
      setEditingCatId(null);
      setEditCatFile(null);
    } catch (e: any) {
      alert(e.message || 'Failed');
    }
  };

  // Delete Category
  const handleDeleteCategory = async (id: string) => {
    if (!confirm('Are you sure you want to delete this category?')) return;
    const updated = categories.filter(c => c.id !== id);
    await handleSaveCategoriesList(updated);
  };

  // Banner Manager save list
  const handleSaveBannersList = async (updated: Banner[]) => {
    try {
      await supabase
        .from('settings')
        .upsert({
          key_name: 'store_hero_banners',
          val_value: JSON.stringify(updated)
        }, { onConflict: 'key_name' });
      setBanners(updated);
    } catch (e) {
      console.error(e);
    }
  };

  // Add Banner Handler
  const handleAddBanner = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newBannerTitle.trim()) return;

    try {
      let imgUrl = null;
      if (newBannerFile) {
        imgUrl = await uploadAssetFile(newBannerFile, 'banners');
      }

      const updated = [...banners, {
        id: Date.now(),
        tag: newBannerTag.trim() || 'Deal',
        title: newBannerTitle.trim(),
        desc: newBannerDesc.trim(),
        link: newBannerLink.trim() || '/shop',
        image: imgUrl,
        theme: newBannerTheme
      }];

      await handleSaveBannersList(updated);
      setNewBannerTag('');
      setNewBannerTitle('');
      setNewBannerDesc('');
      setNewBannerLink('/shop');
      setNewBannerTheme('emerald');
      setNewBannerFile(null);
      setNewBannerPreview('');
      alert('Banner slide added successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed');
    }
  };

  // Save Banner Edit
  const handleSaveBannerEdit = async (id: number) => {
    try {
      let finalImg = editBannerPreview || null;
      if (editBannerFile) {
        finalImg = await uploadAssetFile(editBannerFile, 'banners');
      }

      const updated = banners.map(b => {
        if (b.id === id) {
          return {
            ...b,
            tag: editBannerTag.trim(),
            title: editBannerTitle.trim(),
            desc: editBannerDesc.trim(),
            link: editBannerLink.trim(),
            theme: editBannerTheme,
            image: finalImg
          };
        }
        return b;
      });

      await handleSaveBannersList(updated);
      setEditingBannerId(null);
      setEditBannerFile(null);
    } catch (e: any) {
      alert(e.message || 'Failed');
    }
  };

  // Delete Banner
  const handleDeleteBanner = async (id: number) => {
    if (!confirm('Are you sure you want to delete this banner slide?')) return;
    const updated = banners.filter(b => b.id !== id);
    await handleSaveBannersList(updated);
  };

  // Download Financial Logs CSV
  const handleDownloadLogs = async (type: 'pos' | 'orders') => {
    try {
      let csvContent = "data:text/csv;charset=utf-8,";
      if (type === 'pos') {
        const { data } = await supabase.from('sales').select('*').order('id', { ascending: false });
        if (!data) return;
        csvContent += "ID,Total Amount,Total Profit,Sales Time\n";
        data.forEach(row => {
          csvContent += `${row.id},${row.total_amount},${row.total_profit},${row.sales_time}\n`;
        });
      } else {
        const { data } = await supabase.from('orders').select('*').order('id', { ascending: false });
        if (!data) return;
        csvContent += "ID,Customer Name,Customer Phone,Total Amount,Payment Mode,Status,Date\n";
        data.forEach(row => {
          csvContent += `${row.id},${row.customer_name},${row.customer_phone},${row.total_amount},${row.payment_method},${row.status},${row.created_at}\n`;
        });
      }
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `${type}_financial_logs_${Date.now()}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (e) {
      console.error(e);
    }
  };

  // Demands Confirm/Delete
  const handleConfirmDemand = async (id: number) => {
    try {
      const { error } = await supabase.from('product_demands').update({ status: 'confirmed' }).eq('id', id);
      if (error) throw error;
      setDemands(prev => prev.map(d => d.id === id ? { ...d, status: 'confirmed' as const } : d));
    } catch (e: any) {
      alert(e.message);
    }
  };

  const handleDeleteDemand = async (id: number) => {
    if (!confirm('Are you sure you want to delete this demand?')) return;
    try {
      const { error } = await supabase.from('product_demands').delete().eq('id', id);
      if (error) throw error;
      setDemands(prev => prev.filter(d => d.id !== id));
    } catch (e: any) {
      alert(e.message);
    }
  };

  // Calculations for POS vs Deliveries stream comparison
  const posSales = stats.posSales || 0;
  const onlineSales = stats.onlineSales || 0;
  const totalStreamSales = posSales + onlineSales;
  const posPercentage = totalStreamSales > 0 ? (posSales / totalStreamSales) * 100 : 0;
  const onlinePercentage = totalStreamSales > 0 ? (onlineSales / totalStreamSales) * 100 : 0;

  return (
    <div className="space-y-6 text-slate-800 w-full flex-grow flex flex-col">
      {/* 1. Header Navigation Tabs */}
      <div className="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200 pb-2 gap-4 select-none">
        <nav className="flex gap-6 text-xs uppercase font-extrabold tracking-wider text-slate-400 text-left">
          <button
            onClick={() => setActiveTab('overview')}
            className={`pb-3 border-b-2 transition-all ${
              activeTab === 'overview' ? 'border-emerald-600 text-slate-850' : 'border-transparent hover:text-slate-650'
            }`}
          >
            Overview
          </button>
          <button
            onClick={() => setActiveTab('planner')}
            className={`pb-3 border-b-2 transition-all ${
              activeTab === 'planner' ? 'border-emerald-600 text-slate-850' : 'border-transparent hover:text-slate-650'
            }`}
          >
            Stock Alerts & Planner
          </button>
          <button
            onClick={() => setActiveTab('settings')}
            className={`pb-3 border-b-2 transition-all ${
              activeTab === 'settings' ? 'border-emerald-600 text-slate-850' : 'border-transparent hover:text-slate-650'
            }`}
          >
            Storefront & Themes
          </button>
        </nav>
      </div>

      {/* 2. TAB CONTENT: OVERVIEW */}
      {activeTab === 'overview' && (
        <div className="space-y-6 text-left animate-in fade-in duration-200">
          {/* Stat Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-slate-450 uppercase font-extrabold tracking-wider">Today's POS Sales</span>
                <div className="w-8 h-8 rounded-xl border flex items-center justify-center text-emerald-650 bg-emerald-50 border-emerald-100">
                  <TrendingUp className="w-4 h-4" />
                </div>
              </div>
              <h3 className="text-xl font-black text-slate-850 font-mono">Rs. {posSales.toFixed(0)}</h3>
              <p className="text-[10px] text-slate-400 leading-none">In-Store manual register billing</p>
            </div>
            
            <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-slate-450 uppercase font-extrabold tracking-wider">Online Sales</span>
                <div className="w-8 h-8 rounded-xl border flex items-center justify-center text-blue-600 bg-blue-50 border-blue-100">
                  <ClipboardList className="w-4 h-4" />
                </div>
              </div>
              <h3 className="text-xl font-black text-slate-850 font-mono">Rs. {onlineSales.toFixed(0)}</h3>
              <p className="text-[10px] text-slate-400 leading-none">Delivered online deliveries revenue</p>
            </div>

            <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-slate-450 uppercase font-extrabold tracking-wider">Cumulative Net Profit</span>
                <div className="w-8 h-8 rounded-xl border flex items-center justify-center text-amber-600 bg-amber-50 border-amber-100">
                  <Coins className="w-4 h-4" />
                </div>
              </div>
              <h3 className="text-xl font-black text-slate-850 font-mono">Rs. {stats.netProfit.toFixed(0)}</h3>
              <p className="text-[10px] text-slate-400 leading-none">Actual net income (sales - cogs)</p>
            </div>

            <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-slate-450 uppercase font-extrabold tracking-wider">Order Counter Volumes</span>
                <div className="w-8 h-8 rounded-xl border flex items-center justify-center text-rose-600 bg-rose-50 border-rose-100">
                  <AlertTriangle className="w-4 h-4" />
                </div>
              </div>
              <h3 className="text-xl font-black text-slate-850 font-mono">{stats.activeFulfillments} Total</h3>
              <p className="text-[10px] text-slate-400 leading-none">Total orders registered in backend</p>
            </div>
          </div>

          {/* Revenue stream & staff split */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {/* Revenue comparison & recent activity (2 cols) */}
            <div className="lg:col-span-2 space-y-6 text-left">
              
              {/* Stream Comparison */}
              <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Revenue Stream Comparison</h3>
                <p className="text-[10px] text-slate-400 -mt-2">Comparing values between counter POS operations and online deliveries</p>
                <div className="space-y-4 pt-2">
                  <div className="space-y-1">
                    <div className="flex justify-between text-xs font-bold text-slate-700">
                      <span>In-Store POS Sales ({posPercentage.toFixed(0)}%)</span>
                      <span className="font-mono">Rs. {posSales.toFixed(0)}</span>
                    </div>
                    <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                      <div className="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style={{ width: `${posPercentage}%` }}></div>
                    </div>
                  </div>
                  <div className="space-y-1">
                    <div className="flex justify-between text-xs font-bold text-slate-700">
                      <span>Online Store Deliveries ({onlinePercentage.toFixed(0)}%)</span>
                      <span className="font-mono">Rs. {onlineSales.toFixed(0)}</span>
                    </div>
                    <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                      <div className="bg-blue-500 h-2.5 rounded-full transition-all duration-500" style={{ width: `${onlinePercentage}%` }}></div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Recent Online Orders */}
              <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                <div className="flex items-center justify-between pb-2 border-b border-slate-100">
                  <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Recent Online Orders</h3>
                  <Link href="/admin/orders" className="text-[10px] font-bold text-emerald-600 hover:underline flex items-center gap-1">
                    View All <ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
                <div className="divide-y divide-slate-100">
                  {initialProducts.slice(0, 4).map((p, idx) => (
                    <div key={idx} className="py-3 flex items-center justify-between text-xs">
                      <div className="text-left min-w-0">
                        <span className="font-bold text-slate-800 block truncate">HRT-0000{idx + 1}</span>
                        <span className="text-[10px] text-slate-400">Cash on Delivery</span>
                      </div>
                      <span className="font-mono font-extrabold text-slate-850">Rs. {p.price.toFixed(0)}</span>
                    </div>
                  ))}
                </div>
              </div>

            </div>

            {/* Right side: Add Staff & Registered Staff */}
            <div className="lg:col-span-1 space-y-6 text-left">
              
              {/* Add Staff form */}
              <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4 text-xs">
                <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-2 border-b border-slate-100">
                  <UserPlus className="w-4 h-4 text-emerald-600" />
                  Add Staff Account
                </h3>
                {staffFormError && <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 font-semibold">{staffFormError}</div>}
                {staffFormSuccess && <div className="p-3.5 bg-emerald-50 border border-emerald-250 text-emerald-700 font-semibold">{staffFormSuccess}</div>}
                <form onSubmit={handleRegisterStaff} className="space-y-3.5 text-left">
                  <div className="space-y-1">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Username</label>
                    <input
                      type="text"
                      required
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="e.g. staff.cashier@gmail.com"
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:bg-white focus:border-emerald-500 font-semibold"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
                    <input
                      type="password"
                      required
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:bg-white focus:border-emerald-500 font-mono"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Security Role</label>
                    <select className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:bg-white focus:border-emerald-500 font-semibold">
                      <option value="manager">Store Manager (restricted billing desk)</option>
                      <option value="cashier">Cashier Staff</option>
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Full Name</label>
                    <input
                      type="text"
                      required
                      value={fullName}
                      onChange={(e) => setFullName(e.target.value)}
                      placeholder="e.g. Haroon Ahmed"
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:bg-white focus:border-emerald-500 font-semibold"
                    />
                  </div>
                  <button
                    type="submit"
                    disabled={isSubmittingStaff}
                    className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl transition-all shadow-md active:scale-[0.98] uppercase tracking-wider"
                  >
                    {isSubmittingStaff ? 'Registering...' : 'Register Staff Account'}
                  </button>
                </form>
              </div>

              {/* Registered Staffs */}
              <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-3.5 text-xs text-left">
                <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">Registered Staffs</h3>
                <div className="space-y-3.5">
                  {staffList.map((st) => (
                    <div key={st.id} className="flex items-center justify-between gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-150">
                      <div>
                        <strong className="text-slate-800 font-bold block">{st.name}</strong>
                        <span className="text-[10px] text-slate-400 font-semibold block capitalize">User: {st.role}</span>
                      </div>
                      {st.role !== 'owner' && (
                        <button
                          onClick={() => handleRemoveStaff(st.id, st.name)}
                          className="p-1.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors border border-transparent hover:border-rose-100"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

            </div>
          </div>

          {/* Customer Demands at bottom */}
          <div className="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm space-y-4 text-left">
            <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider select-none text-left flex items-center gap-1.5">
              Customer Demands
              {demands.filter(d => d.status === 'pending').length > 0 && (
                <span className="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-extrabold rounded-full animate-pulse">
                  {demands.filter(d => d.status === 'pending').length} Requests
                </span>
              )}
            </h3>
            <div className="divide-y divide-slate-100 text-xs">
              {demands.length === 0 ? (
                <p className="text-slate-400 text-center py-8">No pending product demands logged from customers.</p>
              ) : (
                demands.slice(0, 3).map((item) => (
                  <div key={item.id} className="py-3 flex items-center justify-between text-left gap-4">
                    <div>
                      <strong className="text-slate-800 block">{item.demand_details}</strong>
                      <span className="text-[10px] text-slate-400 font-semibold block">Requested by: {item.customer_name} ({item.customer_phone})</span>
                    </div>
                    <div className="flex items-center gap-2">
                      {item.status === 'pending' && (
                        <button
                          onClick={() => handleConfirmDemand(item.id)}
                          className="px-2 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded-lg font-bold"
                        >
                          Confirm
                        </button>
                      )}
                      <button
                        onClick={() => handleDeleteDemand(item.id)}
                        className="p-1 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {/* 3. TAB CONTENT: STOCK ALERTS & PLANNER */}
      {activeTab === 'planner' && (
        <div className="bg-white border border-slate-200 rounded-[2.5rem] p-6 sm:p-8 shadow-sm space-y-6 text-left animate-in fade-in duration-200">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
              <h3 className="text-base font-black text-slate-800 tracking-tight">Inventory Stock Planner</h3>
              <p className="text-xs text-slate-400 mt-0.5">Monitor products running low on stock and generate restocking sheets.</p>
            </div>
            
            <div className="flex items-center gap-2 bg-slate-50 p-2.5 rounded-2xl border border-slate-200">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Alert Threshold:</span>
              <input
                type="number"
                value={lowStock}
                onChange={(e) => setLowStock(e.target.value)}
                className="w-16 px-2 py-1 bg-white border border-slate-250 rounded-xl focus:outline-none text-xs text-slate-800 font-mono font-bold"
              />
              <button
                onClick={() => handleSaveSettingsGroup([{ key_name: 'low_stock_threshold', val_value: lowStock }])}
                className="px-3.5 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl active:scale-[0.98] transition-all"
              >
                Save
              </button>
            </div>
          </div>

          {/* Low Stock Items Table */}
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                  <th className="p-3 pl-4">Barcode</th>
                  <th className="p-3">Product Name</th>
                  <th className="p-3">Category</th>
                  <th className="p-3">Current Stock</th>
                  <th className="p-3">Price</th>
                  <th className="p-3">Recommended Restock</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-xs text-slate-700">
                {products.filter(p => p.stock_quantity <= parseInt(lowStock)).length === 0 ? (
                  <tr>
                    <td colSpan={6} className="p-16 text-center text-slate-400 font-semibold select-none">
                      🎉 All catalog products are currently fully stocked!
                    </td>
                  </tr>
                ) : (
                  products.filter(p => p.stock_quantity <= parseInt(lowStock)).map((p) => (
                    <tr key={p.id} className="hover:bg-slate-50/50 transition-colors">
                      <td className="p-3 pl-4 font-mono font-semibold text-slate-450">{p.barcode || '—'}</td>
                      <td className="p-3 font-bold text-slate-800">
                        {p.name} {p.weight ? `(${p.weight} ${p.unit})` : ''}
                      </td>
                      <td className="p-3 uppercase font-semibold text-slate-500 text-[10px]">{p.category.replace('_', ' ')}</td>
                      <td className="p-3">
                        <span className={`px-2.5 py-0.5 rounded font-bold font-mono text-[10px] border ${
                          p.stock_quantity === 0 
                            ? 'bg-rose-50 text-rose-705 border-rose-200 animate-pulse' 
                            : 'bg-amber-50 text-amber-705 border-amber-250'
                        }`}>
                          {p.stock_quantity} left
                        </span>
                      </td>
                      <td className="p-3 font-mono font-bold text-slate-700">Rs. {p.price.toFixed(0)}</td>
                      <td className="p-3 font-bold text-emerald-600 font-mono">+{50 - p.stock_quantity} units</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Action Trigger */}
          <div className="pt-4 border-t border-slate-100 flex justify-start">
            <button
              onClick={handlePrintRestock}
              disabled={products.filter(p => p.stock_quantity <= parseInt(lowStock)).length === 0}
              className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all disabled:opacity-50 disabled:active:scale-100 active:scale-[0.98]"
            >
              <Printer className="w-4 h-4" /> Print Restock Sheet
            </button>
          </div>
        </div>
      )}

      {/* 4. TAB CONTENT: STOREFRONT & THEMES CONFIGURATION PANEL */}
      {activeTab === 'settings' && (
        <div className="space-y-8 animate-in fade-in duration-200 text-xs">
          
          {/* Main Settings Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            {/* Form Fields (2/3 width) */}
            <div className="lg:col-span-2 space-y-6 text-left">
              <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                
                {/* Feedback status alerts */}
                {settingsMessage && (
                  <div className={`p-4 rounded-xl border font-semibold text-xs leading-relaxed ${
                    isSettingsError ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-250'
                  }`}>
                    {settingsMessage}
                  </div>
                )}

                {/* Opening Timings Config */}
                <div className="space-y-4">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Opening Schedule Manager</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Saturday - Thursday</label>
                      <input
                        type="text"
                        value={timingsSatThu}
                        onChange={(e) => setTimingsSatThu(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Friday (Morning)</label>
                      <input
                        type="text"
                        value={timingsFri}
                        onChange={(e) => setTimingsFri(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Friday (Evening)</label>
                      <input
                        type="text"
                        value={timingsFriEve}
                        onChange={(e) => setTimingsFriEve(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([
                      { key_name: 'timings_sat_thu', val_value: timingsSatThu },
                      { key_name: 'timings_fri', val_value: timingsFri },
                      { key_name: 'timings_fri_eve', val_value: timingsFriEve }
                    ])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    UPDATE WEBSITE TIMINGS
                  </button>
                </div>

                {/* Status Announcement settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Storefront Status & Announcements</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1.5 text-left">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shop Operational Status</label>
                      <select
                        value={shopStatus}
                        onChange={(e) => setShopStatus(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold text-left"
                      >
                        <option value="open">OPEN (Customers can checkout)</option>
                        <option value="closed">CLOSED (Prevents orders processing)</option>
                      </select>
                    </div>
                    <div className="space-y-1.5 text-left">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Home Announcement Text</label>
                      <input
                        type="text"
                        value={announcement}
                        onChange={(e) => setAnnouncement(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([
                      { key_name: 'shop_status', val_value: shopStatus },
                      { key_name: 'homepage_announcement', val_value: announcement }
                    ])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE STATUS & ALERTS
                  </button>
                </div>

                {/* WhatsApp Dispatch Template settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">WhatsApp Dispatch Message Template</h3>
                  <div className="space-y-2">
                    <textarea
                      value={dispatchTemplate}
                      onChange={(e) => setDispatchTemplate(e.target.value)}
                      rows={2}
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-mono"
                    />
                    <p className="text-[9px] text-slate-400">Supported variables: &#123;name&#125;, &#123;ref&#125;, &#123;total&#125;, &#123;address&#125;.</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([{ key_name: 'whatsapp_dispatch_template', val_value: dispatchTemplate }])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE MESSAGE TEMPLATE
                  </button>
                </div>

                {/* Detailed Financial Logs & Margins */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Detailed Financial Logs & Margins</h3>
                  <p className="text-[10px] text-slate-400 -mt-2">Download raw spreadsheet log entries in CSV format for analysis or external billing software.</p>
                  <div className="flex flex-wrap items-center gap-3">
                    <button
                      type="button"
                      onClick={() => handleDownloadLogs('pos')}
                      className="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl active:scale-[0.98]"
                    >
                      DOWNLOAD POS SALES LOG
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDownloadLogs('orders')}
                      className="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl active:scale-[0.98]"
                    >
                      DOWNLOAD ONLINE ORDERS LOG
                    </button>
                  </div>
                </div>

                {/* Branding settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Stock, Branding & Currency Settings</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Name</label>
                      <input
                        type="text"
                        value={storeName}
                        onChange={(e) => setStoreName(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Phone</label>
                      <input
                        type="text"
                        value={storePhone}
                        onChange={(e) => setStorePhone(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Email</label>
                      <input
                        type="text"
                        value={storeEmail}
                        onChange={(e) => setStoreEmail(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Address</label>
                      <input
                        type="text"
                        value={storeAddress}
                        onChange={(e) => setStoreAddress(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Currency</label>
                      <input
                        type="text"
                        value={storeCurrency}
                        onChange={(e) => setStoreCurrency(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([
                      { key_name: 'store_name', val_value: storeName },
                      { key_name: 'store_phone', val_value: storePhone },
                      { key_name: 'store_email', val_value: storeEmail },
                      { key_name: 'store_address', val_value: storeAddress },
                      { key_name: 'store_currency', val_value: storeCurrency }
                    ])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE IDENTITY INFO
                  </button>
                </div>

                {/* Boundaries & COD settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Stock Ratio, Boundaries & COD</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Min Order Value (Rs.)</label>
                      <input
                        type="number"
                        value={minOrderValue}
                        onChange={(e) => setMinOrderValue(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold font-mono"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Standard Shipping Fee (Rs.)</label>
                      <input
                        type="number"
                        value={shippingFee}
                        onChange={(e) => setShippingFee(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold font-mono"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([
                      { key_name: 'min_order_value', val_value: minOrderValue },
                      { key_name: 'shipping_fee', val_value: shippingFee }
                    ])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE LIMITS & COD
                  </button>
                </div>

                {/* Google Auth Keys settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Google Auth Keys Settings</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Google OAuth Client ID</label>
                      <input
                        type="text"
                        value={googleClientId}
                        onChange={(e) => setGoogleClientId(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-mono"
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Google OAuth Client Secret</label>
                      <input
                        type="text"
                        value={googleClientSecret}
                        onChange={(e) => setGoogleClientSecret(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-mono"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleSaveSettingsGroup([
                      { key_name: 'google_client_id', val_value: googleClientId },
                      { key_name: 'google_client_secret', val_value: googleClientSecret }
                    ])}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE GOOGLE AUTH SETTINGS
                  </button>
                </div>

                {/* Promotional Alert Popup settings */}
                <div className="border-t border-slate-100 pt-6 space-y-4 text-left">
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Promotional Alert Popup Settings</h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                    <div className="space-y-1.5 text-left">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ad Status</label>
                      <select
                        value={promoAdEnabled}
                        onChange={(e) => setPromoAdEnabled(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-semibold text-left"
                      >
                        <option value="false">INACTIVE (Hidden)</option>
                        <option value="true">ACTIVE (Displayed)</option>
                      </select>
                    </div>
                    <div className="space-y-1.5 text-left">
                      <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Redirect Action Link</label>
                      <input
                        type="text"
                        value={promoAdLink}
                        onChange={(e) => setPromoAdLink(e.target.value)}
                        placeholder="e.g. /shop?category=beverages"
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-mono"
                      />
                    </div>
                  </div>

                  <div className="space-y-2 text-left">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Promo Image Banner</label>
                    <div className="flex items-center gap-4 p-4 border border-dashed border-slate-250 bg-slate-50 rounded-2xl">
                      {(promoAdPreview || promoAdImage) ? (
                        <img src={promoAdPreview || promoAdImage} className="w-16 h-20 object-cover rounded-lg border border-slate-200" />
                      ) : (
                        <div className="w-16 h-20 bg-slate-200 rounded-lg flex items-center justify-center text-[10px] font-bold text-slate-400">No Image</div>
                      )}
                      <div>
                        <label className="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-250 text-slate-655 rounded-xl font-bold cursor-pointer transition-colors">
                          <Upload className="w-3.5 h-3.5" /> Upload File
                          <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                              const file = e.target.files?.[0];
                              if (file) {
                                setPromoAdFile(file);
                                setPromoAdPreview(URL.createObjectURL(file));
                              }
                            }}
                            className="hidden"
                          />
                        </label>
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={async () => {
                      let finalImg = promoAdImage;
                      if (promoAdFile) {
                        finalImg = await uploadAssetFile(promoAdFile, 'promo');
                      }
                      await handleSaveSettingsGroup([
                        { key_name: 'promo_ad_enabled', val_value: promoAdEnabled },
                        { key_name: 'promo_ad_image', val_value: finalImg },
                        { key_name: 'promo_ad_link', val_value: promoAdLink }
                      ]);
                    }}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs active:scale-[0.98] transition-all"
                  >
                    SAVE PROMO POPUP SETTINGS
                  </button>
                </div>

              </div>

              {/* Dynamic Category Manager */}
              <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 text-left">
                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Shop Categories Manager</h3>
                
                {/* Add Category inline form */}
                <form onSubmit={handleAddCategory} className="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end bg-slate-50 p-4 rounded-2xl border border-slate-200 text-left">
                  <div className="space-y-1 sm:col-span-1 text-left">
                    <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Icon File</label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) {
                          setNewCatFile(file);
                          setNewCatPreview(URL.createObjectURL(file));
                        }
                      }}
                      className="w-full text-[10px]"
                    />
                  </div>
                  <div className="space-y-1 sm:col-span-1 text-left">
                    <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">English Name</label>
                    <input
                      type="text"
                      required
                      value={newCatName}
                      onChange={(e) => setNewCatName(e.target.value)}
                      placeholder="e.g. Milk"
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                    />
                  </div>
                  <div className="space-y-1 sm:col-span-1 text-left">
                    <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Urdu Translation</label>
                    <input
                      type="text"
                      value={newCatUrdu}
                      onChange={(e) => setNewCatUrdu(e.target.value)}
                      placeholder="e.g. دودھ"
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-right font-urdu"
                    />
                  </div>
                  <button
                    type="submit"
                    className="w-full py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-lg text-[10px]"
                  >
                    ADD CATEGORY
                  </button>
                </form>

                {/* Categories Table/List */}
                <div className="space-y-3 pt-2">
                  {categories.map((cat) => {
                    const isEditing = editingCatId === cat.id;
                    return (
                      <div key={cat.id} className="flex items-center justify-between p-3 border border-slate-150 rounded-2xl bg-white hover:shadow-inner">
                        {isEditing ? (
                          <div className="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                            <input
                              type="text"
                              value={editCatName}
                              onChange={(e) => setEditCatName(e.target.value)}
                              className="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs"
                            />
                            <input
                              type="text"
                              value={editCatUrdu}
                              onChange={(e) => setEditCatUrdu(e.target.value)}
                              className="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs text-right font-urdu"
                            />
                            <div className="flex gap-2 justify-end">
                              <button onClick={() => handleSaveCategoryEdit(cat.id)} className="px-3 py-1 bg-emerald-600 text-white rounded-lg text-[10px] font-bold">Save</button>
                              <button onClick={() => setEditingCatId(null)} className="px-3 py-1 bg-slate-200 text-slate-605 rounded-lg text-[10px] font-bold">Cancel</button>
                            </div>
                          </div>
                        ) : (
                          <>
                            <div className="flex items-center gap-3 text-left">
                              <div className="w-9 h-9 rounded-full overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center">
                                <img src={cat.image} className="w-6 h-6 object-contain" />
                              </div>
                              <div>
                                <strong className="text-slate-800 font-bold block capitalize">{cat.name}</strong>
                                <span className="text-[10px] text-slate-400 font-mono">Tag: {cat.id}</span>
                              </div>
                            </div>
                            <span className="font-urdu font-bold text-slate-600 text-xs">{cat.urdu}</span>
                            <div className="flex items-center gap-2">
                              <button
                                onClick={() => {
                                  setEditingCatId(cat.id);
                                  setEditCatName(cat.name);
                                  setEditCatUrdu(cat.urdu);
                                  setEditCatPreview(cat.image);
                                  setEditCatFile(null);
                                }}
                                className="p-1 hover:bg-slate-100 text-slate-500 rounded-lg border border-slate-200"
                              >
                                <Edit2 className="w-3.5 h-3.5" />
                              </button>
                              <button
                                onClick={() => handleDeleteCategory(cat.id)}
                                className="p-1 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors border border-transparent hover:border-rose-100"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Dynamic Slider Banners Manager */}
              <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 text-left">
                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Store Hero Banners Manager</h3>
                
                {/* Add Banner form */}
                <form onSubmit={handleAddBanner} className="space-y-4 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-left">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1 text-left">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Tag Label</label>
                      <input
                        type="text"
                        required
                        value={newBannerTag}
                        onChange={(e) => setNewBannerTag(e.target.value)}
                        placeholder="e.g. Premium Choice"
                        className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                      />
                    </div>
                    <div className="space-y-1 text-left">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Banner Title</label>
                      <input
                        type="text"
                        required
                        value={newBannerTitle}
                        onChange={(e) => setNewBannerTitle(e.target.value)}
                        placeholder="Headline"
                        className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                      />
                    </div>
                  </div>
                  <div className="space-y-1 text-left">
                    <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Description</label>
                    <input
                      type="text"
                      value={newBannerDesc}
                      onChange={(e) => setNewBannerDesc(e.target.value)}
                      placeholder="Detailed offer details"
                      className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                    />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                    <div className="space-y-1 text-left">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Redirect Link</label>
                      <input
                        type="text"
                        value={newBannerLink}
                        onChange={(e) => setNewBannerLink(e.target.value)}
                        className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono"
                      />
                    </div>
                    <div className="space-y-1 text-left">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Background Style</label>
                      <select
                        value={newBannerTheme}
                        onChange={(e) => setNewBannerTheme(e.target.value)}
                        className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-left"
                      >
                        <option value="emerald">Emerald Soft</option>
                        <option value="teal">Teal Soft</option>
                        <option value="cyan">Cyan Soft</option>
                      </select>
                    </div>
                    <div className="space-y-1 text-left">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Image File</label>
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => {
                          const file = e.target.files?.[0];
                          if (file) {
                            setNewBannerFile(file);
                            setNewBannerPreview(URL.createObjectURL(file));
                          }
                        }}
                        className="w-full text-[10px]"
                      />
                    </div>
                  </div>
                  <button
                    type="submit"
                    className="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-lg text-[10px]"
                  >
                    ADD CAROUSEL SLIDE
                  </button>
                </form>

                {/* Banners List */}
                <div className="space-y-4 pt-2">
                  {banners.map((banner) => {
                    const isEditing = editingBannerId === banner.id;
                    return (
                      <div key={banner.id} className="p-4 border border-slate-150 rounded-2xl bg-white text-left">
                        {isEditing ? (
                          <div className="space-y-3">
                            <input
                              type="text"
                              value={editBannerTitle}
                              onChange={(e) => setEditBannerTitle(e.target.value)}
                              className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs"
                            />
                            <div className="flex gap-2 justify-end">
                              <button onClick={() => handleSaveBannerEdit(banner.id)} className="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[10px] font-bold">Save</button>
                              <button onClick={() => setEditingBannerId(null)} className="px-3 py-1.5 bg-slate-200 text-slate-650 rounded-lg text-[10px] font-bold">Cancel</button>
                            </div>
                          </div>
                        ) : (
                          <div className="flex flex-col sm:flex-row gap-4 justify-between items-start">
                            <div className="flex-1 space-y-1">
                              <span className="bg-emerald-50 text-emerald-700 text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase">{banner.tag}</span>
                              <strong className="text-slate-800 block text-xs sm:text-sm">{banner.title}</strong>
                              <p className="text-[10px] text-slate-400">{banner.desc}</p>
                            </div>
                            <div className="flex items-center gap-2 self-end sm:self-auto">
                              <button
                                onClick={() => {
                                  setEditingBannerId(banner.id);
                                  setEditBannerTag(banner.tag);
                                  setEditBannerTitle(banner.title);
                                  setEditBannerDesc(banner.desc);
                                  setEditBannerLink(banner.link);
                                  setEditBannerTheme(banner.theme);
                                  setEditBannerPreview(banner.image || '');
                                  setEditBannerFile(null);
                                }}
                                className="p-1.5 hover:bg-slate-100 text-slate-500 rounded-lg border border-slate-200"
                              >
                                <Edit2 className="w-3.5 h-3.5" />
                              </button>
                              <button
                                onClick={() => handleDeleteBanner(banner.id)}
                                className="p-1.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>

            </div>

            {/* Theme Picker Sidebar (1/3 width) */}
            <div className="space-y-6 text-left">
              <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
                <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-100">
                  <Palette className="w-4 h-4 text-emerald-600" />
                  Active System Theme
                </h3>
                <div className="space-y-2">
                  {THEME_OPTIONS.map((theme) => {
                    const isActive = activeTheme === theme.id;
                    return (
                      <button
                        key={theme.id}
                        type="button"
                        onClick={() => {
                          setActiveTheme(theme.id);
                          handleSaveSettingsGroup([{ key_name: 'active_theme', val_value: theme.id }]);
                        }}
                        className={`w-full p-3 rounded-xl border text-xs font-semibold flex items-center gap-3 transition-all ${
                          isActive
                            ? 'border-emerald-500 bg-emerald-50/20 text-slate-850 font-extrabold shadow-sm'
                            : 'border-slate-200 hover:bg-slate-50 text-slate-650'
                        }`}
                      >
                        <div className={`w-4 h-4 rounded-full ${theme.bg} border border-white/20`} />
                        <span className="truncate">{theme.label}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>

          </div>

        </div>
      )}
    </div>
  );
};
