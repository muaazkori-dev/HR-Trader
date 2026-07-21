'use client';

import React, { useState } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Settings, 
  Save, 
  Info, 
  AlertCircle, 
  CheckCircle2, 
  Clock, 
  Palette, 
  Lock,
  Unlock,
  MessageSquare
} from 'lucide-react';
import { useRouter } from 'next/navigation';

interface SettingItem {
  key_name: string;
  val_value: string;
}

interface SettingsContentProps {
  initialSettings: SettingItem[];
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

export const SettingsContent: React.FC<SettingsContentProps> = ({ initialSettings }) => {
  const router = useRouter();

  // Find setting values
  const getValue = (key: string, fallback = '') => {
    return initialSettings.find((s) => s.key_name === key)?.val_value || fallback;
  };

  const [activeTheme, setActiveTheme] = useState(getValue('active_theme', 'emerald_green'));
  const [shopStatus, setShopStatus] = useState(getValue('shop_status', 'open'));
  const [lowStock, setLowStock] = useState(getValue('low_stock_threshold', '5'));
  const [announcement, setAnnouncement] = useState(
    getValue('homepage_announcement', 'Welcome to HR Traders!')
  );
  const [dispatchTemplate, setDispatchTemplate] = useState(
    getValue('whatsapp_dispatch_template', '')
  );
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
  const [minOrderLimitEnabled, setMinOrderLimitEnabled] = useState(getValue('min_order_limit_enabled', 'true'));
  const [firstOrderFreeDelivery, setFirstOrderFreeDelivery] = useState(getValue('first_order_free_delivery', 'true'));

  // Branch 1 locations
  const [branch1Address, setBranch1Address] = useState(getValue('branch_1_address', 'Toor Colony, Front of Hira Public School, Tando Adam'));
  const [branch1Phone, setBranch1Phone] = useState(getValue('branch_1_phone', '+92 303 3943814'));
  const [branch1MapsUrl, setBranch1MapsUrl] = useState(getValue('branch_1_maps_url', 'https://maps.app.goo.gl/ux1364EzVohtCkby7'));

  // Branch 2 locations
  const [branch2Address, setBranch2Address] = useState(getValue('branch_2_address', 'Gulshan-e-Sardar, near Ayoub Hotel, Tando Adam'));
  const [branch2Phone, setBranch2Phone] = useState(getValue('branch_2_phone', '+92 313 7889859'));
  const [branch2MapsUrl, setBranch2MapsUrl] = useState(getValue('branch_2_maps_url', 'https://maps.app.goo.gl/PP2a4Uey6twZvHCKA?g_st=aw'));

  // Business timings
  const [timingsSatThu, setTimingsSatThu] = useState(getValue('timings_sat_thu', '6:00 AM - 12:00 PM'));
  const [timingsFri, setTimingsFri] = useState(getValue('timings_fri', '6:00 AM - 12:00 PM'));
  const [timingsFriEve, setTimingsFriEve] = useState(getValue('timings_fri_eve', '4:00 PM - 12:00 AM'));

  // Promo modal ad states
  const [promoAdEnabled, setPromoAdEnabled] = useState(getValue('promo_ad_enabled', 'false'));
  const [promoAdImage, setPromoAdImage] = useState(getValue('promo_ad_image', ''));
  const [promoAdLink, setPromoAdLink] = useState(getValue('promo_ad_link', ''));
  const [promoAdFile, setPromoAdFile] = useState<File | null>(null);
  const [promoAdPreview, setPromoAdPreview] = useState('');

  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  const handleSaveSettings = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setMessage('');
    setIsError(false);

    const uploadImage = async (file: File): Promise<string> => {
      const fileExt = file.name.split('.').pop();
      const fileName = `promo_${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
      const filePath = `promo/${fileName}`;

      const { error: uploadError } = await supabase.storage
        .from('product-images')
        .upload(filePath, file, { cacheControl: '3600', upsert: true });

      if (uploadError) throw uploadError;

      const { data } = supabase.storage
        .from('product-images')
        .getPublicUrl(filePath);

      return data.publicUrl;
    };

    try {
      let finalPromoImage = promoAdImage;
      if (promoAdFile) {
        finalPromoImage = await uploadImage(promoAdFile);
      }

      const updates = [
        { key_name: 'active_theme', val_value: activeTheme },
        { key_name: 'shop_status', val_value: shopStatus },
        { key_name: 'low_stock_threshold', val_value: lowStock },
        { key_name: 'homepage_announcement', val_value: announcement },
        { key_name: 'whatsapp_dispatch_template', val_value: dispatchTemplate },
        { key_name: 'store_name', val_value: storeName },
        { key_name: 'store_phone', val_value: storePhone },
        { key_name: 'store_email', val_value: storeEmail },
        { key_name: 'store_address', val_value: storeAddress },
        { key_name: 'store_maps_url', val_value: storeMapsUrl },
        { key_name: 'whatsapp_number', val_value: whatsappNumber },
        { key_name: 'facebook_url', val_value: facebookUrl },
        { key_name: 'instagram_url', val_value: instagramUrl },
        { key_name: 'tiktok_url', val_value: tiktokUrl },
        { key_name: 'min_order_value', val_value: minOrderValue },
        { key_name: 'shipping_fee', val_value: shippingFee },
        { key_name: 'store_currency', val_value: storeCurrency },
        { key_name: 'min_order_limit_enabled', val_value: minOrderLimitEnabled },
        { key_name: 'first_order_free_delivery', val_value: firstOrderFreeDelivery },
        { key_name: 'promo_ad_enabled', val_value: promoAdEnabled },
        { key_name: 'promo_ad_image', val_value: finalPromoImage },
        { key_name: 'promo_ad_link', val_value: promoAdLink },
        { key_name: 'branch_1_address', val_value: branch1Address },
        { key_name: 'branch_1_phone', val_value: branch1Phone },
        { key_name: 'branch_1_maps_url', val_value: branch1MapsUrl },
        { key_name: 'branch_2_address', val_value: branch2Address },
        { key_name: 'branch_2_phone', val_value: branch2Phone },
        { key_name: 'branch_2_maps_url', val_value: branch2MapsUrl },
        { key_name: 'timings_sat_thu', val_value: timingsSatThu },
        { key_name: 'timings_fri', val_value: timingsFri },
        { key_name: 'timings_fri_eve', val_value: timingsFriEve },
      ];

      for (const update of updates) {
        const { error } = await supabase
          .from('settings')
          .upsert(update, { onConflict: 'key_name' });
        if (error) throw error;
      }

      setIsError(false);
      setMessage('System configuration settings successfully saved!');
      
      // Refresh page (triggers re-eval of layouts, theme changes immediately!)
      router.refresh();
      setTimeout(() => {
        window.location.reload(); // Hard reload to repaint layout body class
      }, 1000);
    } catch (err: any) {
      setIsError(true);
      setMessage(err.message || 'Failed to save settings to database.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6 w-full flex flex-col flex-1 text-left">
      {/* Header */}
      <section className="pb-4 border-b border-slate-200">
        <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">System Settings</h1>
        <p className="text-xs text-slate-400 mt-1">Configure active themes, storefront announcements, dispatch templates, and warning thresholds.</p>
      </section>

      {/* Main settings form */}
      <form onSubmit={handleSaveSettings} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Left Column: Form config */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            
            {/* Feedback messages */}
            {message && (
              <div className={`p-4 rounded-xl border font-semibold text-xs leading-relaxed ${
                isError 
                  ? 'bg-rose-50 text-rose-700 border-rose-200' 
                  : 'bg-emerald-50 text-emerald-700 border-emerald-200'
              }`}>
                {message}
              </div>
            )}

            {/* Shop Status Toggle */}
            <div className="space-y-2">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Status</label>
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => setShopStatus('open')}
                  className={`flex-1 py-3 px-4 rounded-2xl text-xs font-bold border flex items-center justify-center gap-1.5 transition-all ${
                    shopStatus === 'open'
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200 shadow-sm'
                      : 'bg-white hover:bg-slate-50 text-slate-500 border-slate-200'
                  }`}
                >
                  <Unlock className="w-4 h-4" /> Open Shop
                </button>
                <button
                  type="button"
                  onClick={() => setShopStatus('closed')}
                  className={`flex-1 py-3 px-4 rounded-2xl text-xs font-bold border flex items-center justify-center gap-1.5 transition-all ${
                    shopStatus === 'closed'
                      ? 'bg-rose-50 text-rose-700 border-rose-200 shadow-sm'
                      : 'bg-white hover:bg-slate-50 text-slate-500 border-slate-200'
                  }`}
                >
                  <Lock className="w-4 h-4" /> Temporarily Closed
                </button>
              </div>
              <p className="text-[10px] text-slate-400 leading-normal font-normal">Closed status prevents storefront checkouts.</p>
            </div>

            {/* Announcement Banner */}
            <div className="space-y-1.5">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Banner Announcement</label>
              <textarea
                value={announcement}
                onChange={(e) => setAnnouncement(e.target.value)}
                required
                rows={2}
                placeholder="Alert details displayed on home screen..."
                className="w-full px-4 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 resize-none font-semibold"
              />
            </div>

            {/* Low stock threshold */}
            <div className="space-y-1.5">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Low Stock warning Limit</label>
              <input
                type="number"
                value={lowStock}
                onChange={(e) => setLowStock(e.target.value)}
                required
                min={1}
                className="w-32 px-4 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-mono font-bold"
              />
              <p className="text-[10px] text-slate-400 font-normal">Highlight item in red inside admin panels if stock falls below this quantity.</p>
            </div>

            {/* Dispatch SMS Template */}
            <div className="space-y-2">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">WhatsApp Dispatch Message Template</label>
              <textarea
                value={dispatchTemplate}
                onChange={(e) => setDispatchTemplate(e.target.value)}
                rows={3}
                placeholder="e.g. Hi {name}, your order #{ref} is out for delivery! Total: {total}..."
                className="w-full px-4 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-mono leading-relaxed"
              />
              <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl flex gap-2 items-start text-[10px] text-slate-500 leading-normal font-normal">
                <Info className="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                <div>
                  Supported Dynamic Placeholders:
                  <code className="bg-white border border-slate-200 px-1 py-0.5 rounded mx-1 font-bold text-slate-700 font-mono">&#123;name&#125;</code>, 
                  <code className="bg-white border border-slate-200 px-1 py-0.5 rounded mx-1 font-bold text-slate-700 font-mono">&#123;ref&#125;</code>, 
                  <code className="bg-white border border-slate-200 px-1 py-0.5 rounded mx-1 font-bold text-slate-700 font-mono">&#123;total&#125;</code>, 
                  <code className="bg-white border border-slate-200 px-1 py-0.5 rounded mx-1 font-bold text-slate-700 font-mono">&#123;address&#125;</code>
                </div>
              </div>
            </div>

            {/* Promo Modal Ad Settings */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Fullscreen Promo Modal Ad / ہوم اسکرین اشتہار
              </h3>
              
              <div className="space-y-4">
                {/* Enabled Toggle */}
                <div className="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                  <div className="text-left">
                    <label className="block text-[10px] font-bold text-slate-650 uppercase tracking-wider">Display Popup Ad on Website Load</label>
                    <p className="text-[9px] text-slate-405 leading-normal font-normal">Saves a session flag so it only shows once per customer visit.</p>
                  </div>
                  <select
                    value={promoAdEnabled}
                    onChange={(e) => setPromoAdEnabled(e.target.value)}
                    className="px-3.5 py-1.5 rounded-xl text-[10px] font-bold border bg-white border-slate-250 text-slate-800 focus:outline-none focus:border-emerald-500"
                  >
                    <option value="false">INACTIVE (Hidden)</option>
                    <option value="true">ACTIVE (Displayed)</option>
                  </select>
                </div>

                {/* Redirect Link Input */}
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Ad Action / Redirect URL (Optional)</label>
                  <input
                    type="text"
                    value={promoAdLink}
                    onChange={(e) => setPromoAdLink(e.target.value)}
                    placeholder="e.g. /shop?category=ice_cream"
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-mono"
                  />
                  <p className="text-[9px] text-slate-400 font-normal">Action triggered when customer clicks the image. Leave blank for no redirect.</p>
                </div>

                {/* Image Upload Input */}
                <div className="space-y-2">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Ad Banner Image</label>
                  <div className="flex flex-col sm:flex-row items-center gap-4 p-4 border border-dashed border-slate-350 bg-slate-50 rounded-2xl">
                    {/* Image Preview */}
                    {(promoAdPreview || promoAdImage) ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img
                        src={promoAdPreview || promoAdImage}
                        alt="Promo Preview"
                        className="w-20 h-24 object-cover rounded-xl border border-slate-250 shadow-sm"
                      />
                    ) : (
                      <div className="w-20 h-24 bg-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-[10px] font-bold">
                        No Image
                      </div>
                    )}
                    
                    <div className="flex-1 w-full text-left space-y-1">
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
                        className="text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 file:cursor-pointer"
                      />
                      <p className="text-[9px] text-slate-400 font-normal">Choose a premium vertical/square poster image. Max size: 10MB.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Store Information */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Store Branding & currency</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Store Name</label>
                  <input
                    type="text"
                    value={storeName}
                    onChange={(e) => setStoreName(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Store Currency Symbol</label>
                  <input
                    type="text"
                    value={storeCurrency}
                    onChange={(e) => setStoreCurrency(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-semibold"
                  />
                </div>
              </div>
            </div>

            {/* Branch 1 Location Config */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Branch 1 Location Details</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px]">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Branch 1 Address</label>
                  <input
                    type="text"
                    value={branch1Address}
                    onChange={(e) => setBranch1Address(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Branch 1 Phone</label>
                  <input
                    type="text"
                    value={branch1Phone}
                    onChange={(e) => setBranch1Phone(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-semibold"
                  />
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="block text-[10px] font-bold text-slate-505 uppercase tracking-wider text-left">Branch 1 Google Maps Link</label>
                <input
                  type="text"
                  value={branch1MapsUrl}
                  onChange={(e) => setBranch1MapsUrl(e.target.value)}
                  required
                  placeholder="https://maps.app.goo.gl/..."
                  className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-mono"
                />
              </div>
            </div>

            {/* Branch 2 Location Config */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Branch 2 Location Details</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px]">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-505 uppercase tracking-wider text-left">Branch 2 Address</label>
                  <input
                    type="text"
                    value={branch2Address}
                    onChange={(e) => setBranch2Address(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-505 uppercase tracking-wider text-left">Branch 2 Phone</label>
                  <input
                    type="text"
                    value={branch2Phone}
                    onChange={(e) => setBranch2Phone(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-semibold"
                  />
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="block text-[10px] font-bold text-slate-505 uppercase tracking-wider text-left">Branch 2 Google Maps Link</label>
                <input
                  type="text"
                  value={branch2MapsUrl}
                  onChange={(e) => setBranch2MapsUrl(e.target.value)}
                  required
                  placeholder="https://maps.app.goo.gl/..."
                  className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-850 font-mono"
                />
              </div>
            </div>

            {/* Business Timings Config */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Store Business Timings</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-[11px]">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-505 uppercase tracking-wider text-left">Saturday - Thursday</label>
                  <input
                    type="text"
                    value={timingsSatThu}
                    onChange={(e) => setTimingsSatThu(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-550 uppercase tracking-wider text-left">Friday (Morning)</label>
                  <input
                    type="text"
                    value={timingsFri}
                    onChange={(e) => setTimingsFri(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-550 uppercase tracking-wider text-left">Friday (Evening)</label>
                  <input
                    type="text"
                    value={timingsFriEve}
                    onChange={(e) => setTimingsFriEve(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-semibold"
                  />
                </div>
              </div>
            </div>

            {/* Checkout Config */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Checkout Configurations</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">WhatsApp Contact</label>
                  <input
                    type="text"
                    value={whatsappNumber}
                    onChange={(e) => setWhatsappNumber(e.target.value)}
                    required
                    placeholder="e.g. 03337155323"
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-semibold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Min Order Value (Rs.)</label>
                  <input
                    type="number"
                    value={minOrderValue}
                    onChange={(e) => setMinOrderValue(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Standard Shipping (Rs.)</label>
                  <input
                    type="number"
                    value={shippingFee}
                    onChange={(e) => setShippingFee(e.target.value)}
                    required
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-805 font-mono font-bold"
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Minimum Order Limit</label>
                  <select
                    value={minOrderLimitEnabled}
                    onChange={(e) => setMinOrderLimitEnabled(e.target.value)}
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-850 font-semibold"
                  >
                    <option value="true">Enabled (Apply Limit)</option>
                    <option value="false">Disabled (No Minimum Limit)</option>
                  </select>
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">First-time Free Delivery</label>
                  <select
                    value={firstOrderFreeDelivery}
                    onChange={(e) => setFirstOrderFreeDelivery(e.target.value)}
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-850 font-semibold"
                  >
                    <option value="true">Enabled (First Order Free)</option>
                    <option value="false">Disabled (Always Charge Delivery)</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Social Media Linkage */}
            <div className="border-t border-slate-100 pt-6 space-y-4">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider select-none text-left">Social Media Links</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Facebook Page URL</label>
                  <input
                    type="text"
                    value={facebookUrl}
                    onChange={(e) => setFacebookUrl(e.target.value)}
                    placeholder="https://facebook.com/..."
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-650"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Instagram URL</label>
                  <input
                    type="text"
                    value={instagramUrl}
                    onChange={(e) => setInstagramUrl(e.target.value)}
                    placeholder="https://instagram.com/..."
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-650"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">TikTok Profile URL</label>
                  <input
                    type="text"
                    value={tiktokUrl}
                    onChange={(e) => setTiktokUrl(e.target.value)}
                    placeholder="https://tiktok.com/..."
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs text-slate-650"
                  />
                </div>
              </div>
            </div>

            {/* Save Button */}
            <div className="pt-4 border-t border-slate-100">
              <button
                type="submit"
                disabled={submitting}
                className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all disabled:bg-slate-350"
              >
                <Save className="w-4 h-4" />
                {submitting ? 'Saving Configuration...' : 'Save Configuration settings'}
              </button>
            </div>

          </div>
        </div>

        {/* Right Column: Theme picker */}
        <div className="space-y-6">
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
                    onClick={() => setActiveTheme(theme.id)}
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

      </form>
    </div>
  );
};
