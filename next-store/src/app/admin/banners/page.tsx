'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Sparkles,
  Plus,
  Trash2,
  Edit2,
  Save,
  X,
  Upload,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Link as LinkIcon,
  Image as ImageIcon
} from 'lucide-react';
import Link from 'next/link';

interface Banner {
  id: number;
  tag: string;
  title: string;
  desc: string;
  link: string;
  image: string | null;
  theme: string; // 'emerald', 'teal', 'cyan'
}

const DEFAULT_BANNERS: Banner[] = [
  {
    id: 1,
    tag: 'Premium Choice',
    title: 'Your Premium Grocery Partner',
    desc: 'Fresh organic crops, groceries, and premium household brands delivered straight to your home.',
    link: '/shop',
    image: '/assets/images/hero_grocery_banner.png',
    theme: 'emerald'
  },
  {
    id: 2,
    tag: 'Beat The Heat',
    title: 'Quench Your Thirst',
    desc: 'Soft drinks, juices, mineral water bottles, and energy drinks delivered straight to your doorstep ice cold.',
    link: '/shop?category=beverages',
    image: null,
    theme: 'teal'
  },
  {
    id: 3,
    tag: 'Frozen Delights',
    title: 'Frozen Ice Creams',
    desc: 'Family pack ice creams and chicken frozen snacks. *Available for nearby locations to maintain cold chain.',
    link: '/shop?category=ice_cream',
    image: null,
    theme: 'cyan'
  }
];

export default function AdminBannersPage() {
  const [banners, setBanners] = useState<Banner[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Add Banner form state
  const [newTag, setNewTag] = useState('');
  const [newTitle, setNewTitle] = useState('');
  const [newDesc, setNewDesc] = useState('');
  const [newLink, setNewLink] = useState('/shop');
  const [newTheme, setNewTheme] = useState('emerald');
  const [newBannerFile, setNewBannerFile] = useState<File | null>(null);
  const [newBannerPreview, setNewBannerPreview] = useState('');

  // Editing state
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editTag, setEditTag] = useState('');
  const [editTitle, setEditTitle] = useState('');
  const [editDesc, setEditDesc] = useState('');
  const [editLink, setEditLink] = useState('');
  const [editTheme, setEditTheme] = useState('emerald');
  const [editFile, setEditFile] = useState<File | null>(null);
  const [editPreview, setEditPreview] = useState('');

  useEffect(() => {
    fetchBanners();
  }, []);

  const fetchBanners = async () => {
    setLoading(true);
    try {
      const { data, error } = await supabase
        .from('settings')
        .select('val_value')
        .eq('key_name', 'store_hero_banners')
        .maybeSingle();

      if (error) throw error;

      if (data?.val_value) {
        setBanners(JSON.parse(data.val_value));
      } else {
        setBanners(DEFAULT_BANNERS);
      }
    } catch (err: any) {
      console.error('Error fetching banners:', err);
      setMessage(err.message || 'Failed to fetch banners.');
      setIsError(true);
    } finally {
      setLoading(false);
    }
  };

  const uploadBannerImage = async (file: File): Promise<string> => {
    const fileExt = file.name.split('.').pop();
    const fileName = `banner_${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
    const filePath = `banners/${fileName}`;

    const { error: uploadError } = await supabase.storage
      .from('product-images')
      .upload(filePath, file, { cacheControl: '3600', upsert: true });

    if (uploadError) throw uploadError;

    const { data } = supabase.storage
      .from('product-images')
      .getPublicUrl(filePath);

    return data.publicUrl;
  };

  const saveToDatabase = async (updatedList: Banner[]) => {
    const { error } = await supabase
      .from('settings')
      .upsert({
        key_name: 'store_hero_banners',
        val_value: JSON.stringify(updatedList)
      }, { onConflict: 'key_name' });

    if (error) throw error;
  };

  const handleAddBanner = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTitle.trim() || !newTag.trim()) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      let imageUrl: string | null = null;
      if (newBannerFile) {
        imageUrl = await uploadBannerImage(newBannerFile);
      }

      const newBanner: Banner = {
        id: Date.now(),
        tag: newTag.trim(),
        title: newTitle.trim(),
        desc: newDesc.trim(),
        link: newLink.trim() || '/shop',
        image: imageUrl,
        theme: newTheme
      };

      const updatedList = [...banners, newBanner];
      await saveToDatabase(updatedList);
      
      setBanners(updatedList);
      setNewTag('');
      setNewTitle('');
      setNewDesc('');
      setNewLink('/shop');
      setNewTheme('emerald');
      setNewBannerFile(null);
      setNewBannerPreview('');
      setMessage('New hero slider banner added successfully!');
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to add hero banner.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleStartEdit = (banner: Banner) => {
    setEditingId(banner.id);
    setEditTag(banner.tag);
    setEditTitle(banner.title);
    setEditDesc(banner.desc);
    setEditLink(banner.link);
    setEditTheme(banner.theme);
    setEditPreview(banner.image || '');
    setEditFile(null);
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setEditFile(null);
    setEditPreview('');
  };

  const handleSaveEdit = async (id: number) => {
    if (!editTitle.trim() || !editTag.trim()) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      let finalImageUrl = editPreview || null;
      if (editFile) {
        finalImageUrl = await uploadBannerImage(editFile);
      }

      const updatedList = banners.map(b => {
        if (b.id === id) {
          return {
            ...b,
            tag: editTag.trim(),
            title: editTitle.trim(),
            desc: editDesc.trim(),
            link: editLink.trim() || '/shop',
            image: finalImageUrl,
            theme: editTheme
          };
        }
        return b;
      });

      await saveToDatabase(updatedList);
      setBanners(updatedList);
      setEditingId(null);
      setEditFile(null);
      setMessage('Hero banner updated successfully!');
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to update hero banner.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteBanner = async (id: number, title: string) => {
    if (!window.confirm(`Are you sure you want to delete the banner "${title}"?`)) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      const updatedList = banners.filter(b => b.id !== id);
      await saveToDatabase(updatedList);
      
      setBanners(updatedList);
      setMessage(`Banner "${title}" deleted successfully!`);
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to delete hero banner.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleNewFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setNewBannerFile(file);
      setNewBannerPreview(URL.createObjectURL(file));
    }
  };

  const handleEditFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setEditFile(file);
      setEditPreview(URL.createObjectURL(file));
    }
  };

  return (
    <div className="space-y-6 text-left w-full flex-grow flex flex-col">
      {/* Header */}
      <section className="pb-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-emerald-600" />
            Homepage Banner Slider Management
          </h1>
          <p className="text-xs text-slate-400 mt-1">Add, update, style, and manage your dynamic storefront hero carousel slides.</p>
        </div>
        <Link 
          href="/admin/settings" 
          className="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-250 px-3 py-1.5 rounded-xl font-bold self-start sm:self-auto"
        >
          <ArrowLeft className="w-3.5 h-3.5" /> Back to Settings
        </Link>
      </section>

      {/* Notification banner */}
      {message && (
        <div className={`p-4 rounded-2xl border flex items-start gap-2.5 text-xs font-semibold ${
          isError ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'
        }`}>
          {isError ? <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" /> : <CheckCircle2 className="w-4 h-4 mt-0.5 flex-shrink-0" />}
          <span>{message}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        {/* Left Column: Active Banners (2/3 width) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm">
            <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-5 flex items-center gap-2">
              <Sparkles className="w-4.5 h-4.5 text-emerald-500" />
              Active Slider Banners ({banners.length})
            </h3>

            {loading ? (
              <div className="py-20 text-center text-slate-400 text-xs font-semibold">
                Loading slider desk...
              </div>
            ) : banners.length === 0 ? (
              <div className="py-20 text-center text-slate-400 text-xs">
                No active sliders found in database.
              </div>
            ) : (
              <div className="space-y-4">
                {banners.map((banner, index) => {
                  const isEditing = editingId === banner.id;

                  return (
                    <div 
                      key={banner.id}
                      className={`border rounded-3xl p-5 sm:p-6 transition-all duration-200 ${
                        isEditing 
                          ? 'border-emerald-500 bg-emerald-50/10 shadow-sm' 
                          : 'border-slate-100 hover:border-slate-200 hover:shadow-sm'
                      }`}
                    >
                      {/* Editing state */}
                      {isEditing ? (
                        <div className="space-y-4 text-xs">
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                              <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Tag</label>
                              <input 
                                type="text"
                                value={editTag}
                                onChange={(e) => setEditTag(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 focus:outline-none focus:border-emerald-500"
                                placeholder="e.g. Premium Choice, Flat 20% Off"
                              />
                            </div>
                            <div className="space-y-1.5">
                              <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Title</label>
                              <input 
                                type="text"
                                value={editTitle}
                                onChange={(e) => setEditTitle(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 focus:outline-none focus:border-emerald-500"
                                placeholder="Banner Headline"
                              />
                            </div>
                          </div>

                          <div className="space-y-1.5">
                            <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Description</label>
                            <textarea 
                              value={editDesc}
                              onChange={(e) => setEditDesc(e.target.value)}
                              rows={2}
                              className="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 focus:outline-none focus:border-emerald-500"
                              placeholder="Describe this offer..."
                            />
                          </div>

                          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div className="space-y-1.5">
                              <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Redirect Link</label>
                              <input 
                                type="text"
                                value={editLink}
                                onChange={(e) => setEditLink(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 focus:outline-none focus:border-emerald-500"
                                placeholder="e.g. /shop?category=beverages"
                              />
                            </div>
                            <div className="space-y-1.5">
                              <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Theme Style</label>
                              <select
                                value={editTheme}
                                onChange={(e) => setEditTheme(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 focus:outline-none focus:border-emerald-500"
                              >
                                <option value="emerald">Emerald Soft</option>
                                <option value="teal">Teal Soft</option>
                                <option value="cyan">Cyan Soft</option>
                              </select>
                            </div>
                            
                            <div className="space-y-1.5">
                              <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Background Image</label>
                              <div className="flex items-center gap-3">
                                {editPreview && (
                                  <div className="w-9 h-9 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <img src={editPreview} className="object-cover w-full h-full" />
                                  </div>
                                )}
                                <label className="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-150 border border-slate-250 text-slate-650 rounded-xl font-bold cursor-pointer transition-colors hover:bg-slate-200">
                                  <Upload className="w-3.5 h-3.5" /> Upload File
                                  <input type="file" onChange={handleEditFileChange} accept="image/*" className="hidden" />
                                </label>
                              </div>
                            </div>
                          </div>

                          <div className="flex gap-2 justify-end pt-3 border-t border-slate-100">
                            <button
                              onClick={handleCancelEdit}
                              disabled={submitting}
                              className="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-250 text-slate-600 rounded-lg font-bold flex items-center gap-1 transition-colors"
                            >
                              <X className="w-3.5 h-3.5" /> Cancel
                            </button>
                            <button
                              onClick={() => handleSaveEdit(banner.id)}
                              disabled={submitting || !editTitle.trim()}
                              className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold flex items-center gap-1 transition-colors shadow-sm"
                            >
                              <Save className="w-3.5 h-3.5" /> Save Changes
                            </button>
                          </div>
                        </div>
                      ) : (
                        // Normal layout view
                        <div className="space-y-4">
                          <div className="flex flex-col sm:flex-row gap-4 justify-between items-start">
                            <div className="space-y-2 flex-1">
                              <div className="flex items-center gap-2">
                                <span className="bg-slate-100 text-slate-700 text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                  Slide {index + 1} ({banner.theme} style)
                                </span>
                                <span className="bg-emerald-50 text-emerald-700 text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                  {banner.tag}
                                </span>
                              </div>
                              <h4 className="font-extrabold text-slate-800 text-sm sm:text-base tracking-tight leading-tight">
                                {banner.title}
                              </h4>
                              <p className="text-slate-500 text-[10px] sm:text-xs leading-relaxed max-w-xl">
                                {banner.desc}
                              </p>
                              
                              <div className="flex flex-wrap items-center gap-4 text-[10px] text-slate-400 font-semibold pt-1">
                                <span className="flex items-center gap-1 text-emerald-650">
                                  <LinkIcon className="w-3.5 h-3.5" />
                                  Link: {banner.link}
                                </span>
                                {banner.image && (
                                  <span className="flex items-center gap-1 text-blue-500">
                                    <ImageIcon className="w-3.5 h-3.5" /> Custom BG Image Active
                                  </span>
                                )}
                              </div>
                            </div>

                            {banner.image ? (
                              <div className="w-full sm:w-28 h-20 rounded-2xl overflow-hidden border border-slate-100 bg-slate-50 flex items-center justify-center flex-shrink-0 shadow-inner relative group">
                                <img src={banner.image} className="w-full h-full object-cover" />
                              </div>
                            ) : (
                              <div className="w-full sm:w-28 h-20 rounded-2xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 text-slate-350">
                                <ImageIcon className="w-5 h-5" />
                              </div>
                            )}
                          </div>

                          <div className="pt-3 border-t border-slate-150/60 flex justify-end gap-2">
                            <button
                              onClick={() => handleStartEdit(banner)}
                              className="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 hover:text-emerald-650 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl transition-colors"
                            >
                              <Edit2 className="w-3.5 h-3.5" /> Edit
                            </button>
                            <button
                              onClick={() => handleDeleteBanner(banner.id, banner.title)}
                              className="inline-flex items-center gap-1 text-[10px] font-bold text-rose-500 hover:bg-rose-50 border border-transparent px-3 py-1.5 rounded-xl transition-colors"
                            >
                              <Trash2 className="w-3.5 h-3.5" /> Delete
                            </button>
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Add Banner Form (1/3 width) */}
        <div className="space-y-6">
          <div className="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm">
            <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-5 flex items-center gap-2">
              <Plus className="w-4.5 h-4.5 text-emerald-500" />
              Add Slider Banner
            </h3>

            <form onSubmit={handleAddBanner} className="space-y-4 text-xs">
              {/* Tag Label */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Tag Label</label>
                <input 
                  type="text" 
                  value={newTag}
                  onChange={(e) => setNewTag(e.target.value)}
                  placeholder="e.g. Premium Choice, Flat Discounts"
                  required
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                />
              </div>

              {/* Title */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Banner Title</label>
                <input 
                  type="text" 
                  value={newTitle}
                  onChange={(e) => setNewTitle(e.target.value)}
                  placeholder="Headline for slider card"
                  required
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                />
              </div>

              {/* Description */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Description</label>
                <textarea 
                  value={newDesc}
                  onChange={(e) => setNewDesc(e.target.value)}
                  placeholder="Slide description details..."
                  rows={2}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                />
              </div>

              {/* Redirect link */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Shop Redirect URL</label>
                <input 
                  type="text" 
                  value={newLink}
                  onChange={(e) => setNewLink(e.target.value)}
                  placeholder="e.g. /shop or /shop?category=anaj"
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                />
              </div>

              {/* Background Theme style */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Background Color Style</label>
                <select
                  value={newTheme}
                  onChange={(e) => setNewTheme(e.target.value)}
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                >
                  <option value="emerald">Emerald Soft</option>
                  <option value="teal">Teal Soft</option>
                  <option value="cyan">Cyan Soft</option>
                </select>
              </div>

              {/* Banner Background Image (Optional) */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Custom Background Image (Optional)</label>
                <div className="flex items-center gap-4">
                  {newBannerPreview && (
                    <div className="w-14 h-10 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 shadow-inner">
                      <img src={newBannerPreview} className="object-cover w-full h-full" />
                    </div>
                  )}
                  
                  <div className="flex-1">
                    <label className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-250 text-slate-650 rounded-xl font-bold cursor-pointer transition-colors">
                      <Upload className="w-3.5 h-3.5" /> Upload BG Image
                      <input 
                        type="file" 
                        onChange={handleNewFileChange} 
                        accept="image/*" 
                        className="hidden" 
                      />
                    </label>
                    <p className="text-[9px] text-slate-400 mt-1">If set, overrides background theme color.</p>
                  </div>
                </div>
              </div>

              {/* Submit btn */}
              <button
                type="submit"
                disabled={submitting || !newTitle.trim() || !newTag.trim()}
                className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-2.5 rounded-xl transition-all shadow-md active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-1.5 text-xs"
              >
                {submitting ? 'Adding Banner Slide...' : (
                  <>
                    <Plus className="w-4.5 h-4.5" /> Add Banner Slide
                  </>
                )}
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  );
}
