'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Folder, 
  ShoppingBag, 
  Sparkles,
  Layers,
  Plus,
  Trash2,
  Edit2,
  Save,
  X,
  Upload,
  ArrowLeft,
  CheckCircle2,
  AlertCircle
} from 'lucide-react';
import Link from 'next/link';

interface Category {
  id: string;
  name: string;
  urdu: string;
  image: string;
}

const DEFAULT_CATEGORIES: Category[] = [
  { id: 'anaj', name: 'Anaj', urdu: 'اناج', image: '/assets/images/categories/anaj.png' },
  { id: 'grocery', name: 'Grocery', urdu: 'گروسری', image: '/assets/images/categories/grocery.png' },
  { id: 'ice_cream', name: 'Ice Cream', urdu: 'آئس کریم', image: '/assets/images/categories/ice_cream.png' },
  { id: 'beverages', name: 'Beverages', urdu: 'مشروبات', image: '/assets/images/categories/cold_drinks.png' },
  { id: 'milk', name: 'Milk', urdu: 'دودھ', image: '/assets/images/categories/milk.png' },
  { id: 'cosmetics', name: 'Cosmetics', urdu: 'کاسمیٹکس', image: '/assets/images/categories/cosmetics.png' },
  { id: 'confectionary', name: 'Snacks', urdu: 'سنیکس', image: '/assets/images/categories/snacks.png' },
  { id: 'bakery', name: 'Bakery', urdu: 'بیکری', image: '/assets/images/categories/bakery.png' },
  { id: 'sauce', name: 'Sauces', urdu: 'سوس', image: '/assets/images/categories/sauce.png' },
];

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Add Category form state
  const [newCatName, setNewCatName] = useState('');
  const [newCatUrdu, setNewCatUrdu] = useState('');
  const [newCatFile, setNewCatFile] = useState<File | null>(null);
  const [newCatPreview, setNewCatPreview] = useState('');

  // Editing state
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editName, setEditName] = useState('');
  const [editUrdu, setEditUrdu] = useState('');
  const [editFile, setEditFile] = useState<File | null>(null);
  const [editPreview, setEditPreview] = useState('');

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const { data, error } = await supabase
        .from('settings')
        .select('val_value')
        .eq('key_name', 'store_categories')
        .maybeSingle();

      if (error) throw error;

      if (data?.val_value) {
        const parsed = JSON.parse(data.val_value);
        let parsedList: Category[] = [];
        if (typeof parsed === 'object' && !Array.isArray(parsed)) {
          parsedList = Object.entries(parsed).map(([id, val]: any) => ({
            id,
            name: val.name,
            urdu: val.urdu || '',
            image: val.image || `/assets/images/categories/${id}.png`
          }));
        } else if (Array.isArray(parsed)) {
          parsedList = parsed.map((cat: any) => ({
            id: cat.id || cat.key,
            name: cat.name,
            urdu: cat.urdu || '',
            image: cat.image || `/assets/images/categories/${cat.id || cat.key}.png`
          }));
        }
        setCategories(parsedList);
      } else {
        // Fallback to default
        setCategories(DEFAULT_CATEGORIES);
      }
    } catch (err: any) {
      console.error('Error fetching categories:', err);
      setMessage(err.message || 'Failed to fetch categories.');
      setIsError(true);
    } finally {
      setLoading(false);
    }
  };

  const uploadIcon = async (file: File, key: string): Promise<string> => {
    const fileExt = file.name.split('.').pop();
    const fileName = `${key}_${Math.random().toString(36).substring(2)}.${fileExt}`;
    const filePath = `categories/${fileName}`;

    const { error: uploadError } = await supabase.storage
      .from('product-images')
      .upload(filePath, file, { cacheControl: '3600', upsert: true });

    if (uploadError) throw uploadError;

    const { data } = supabase.storage
      .from('product-images')
      .getPublicUrl(filePath);

    return data.publicUrl;
  };

  const saveToDatabase = async (updatedList: Category[]) => {
    // Save to settings table
    const { error } = await supabase
      .from('settings')
      .upsert({
        key_name: 'store_categories',
        val_value: JSON.stringify(updatedList)
      }, { onConflict: 'key_name' });

    if (error) throw error;
  };

  const handleAddCategory = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCatName.trim()) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      // 1. Generate unique clean key
      let key = newCatName.toLowerCase().replace(/[^a-z0-9]/g, '_');
      key = key.replace(/_+/g, '_').replace(/^_+|_+$/g, '');
      if (!key) key = 'cat_' + Date.now();

      // Check duplicate key
      if (categories.some(c => c.id === key)) {
        key = key + '_' + Date.now().toString().slice(-4);
      }

      // 2. Upload image if selected
      let imageUrl = `/assets/images/categories/${key}.png`; // fallback path
      if (newCatFile) {
        imageUrl = await uploadIcon(newCatFile, key);
      }

      // 3. Add to list
      const newCategory: Category = {
        id: key,
        name: newCatName.trim(),
        urdu: newCatUrdu.trim(),
        image: imageUrl
      };

      const updatedList = [...categories, newCategory];
      await saveToDatabase(updatedList);
      
      setCategories(updatedList);
      setNewCatName('');
      setNewCatUrdu('');
      setNewCatFile(null);
      setNewCatPreview('');
      setMessage(`Category "${newCategory.name}" added successfully!`);
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to add category.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleStartEdit = (cat: Category) => {
    setEditingId(cat.id);
    setEditName(cat.name);
    setEditUrdu(cat.urdu);
    setEditPreview(cat.image);
    setEditFile(null);
  };

  const handleCancelEdit = () => {
    setEditingId(null);
    setEditFile(null);
    setEditPreview('');
  };

  const handleSaveEdit = async (id: string) => {
    if (!editName.trim()) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      let finalImageUrl = editPreview;
      if (editFile) {
        finalImageUrl = await uploadIcon(editFile, id);
      }

      const updatedList = categories.map(cat => {
        if (cat.id === id) {
          return {
            ...cat,
            name: editName.trim(),
            urdu: editUrdu.trim(),
            image: finalImageUrl
          };
        }
        return cat;
      });

      await saveToDatabase(updatedList);
      setCategories(updatedList);
      setEditingId(null);
      setEditFile(null);
      setMessage('Category updated successfully!');
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to update category.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteCategory = async (id: string, name: string) => {
    if (!window.confirm(`Are you sure you want to delete the category "${name}"?`)) return;

    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      const updatedList = categories.filter(c => c.id !== id);
      await saveToDatabase(updatedList);
      
      setCategories(updatedList);
      setMessage(`Category "${name}" deleted successfully!`);
      setIsError(false);
    } catch (err: any) {
      console.error(err);
      setMessage(err.message || 'Failed to delete category.');
      setIsError(true);
    } finally {
      setSubmitting(false);
    }
  };

  const handleNewFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setNewCatFile(file);
      setNewCatPreview(URL.createObjectURL(file));
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
            <Layers className="w-5 h-5 text-emerald-600" />
            Categories Management
          </h1>
          <p className="text-xs text-slate-400 mt-1">Add, update, rename categories, and upload custom icons.</p>
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
        
        {/* Left Column: List of Categories (2/3 width) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm">
            <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-5 flex items-center gap-2">
              <Layers className="w-4.5 h-4.5 text-emerald-500" />
              Active Storefront Categories ({categories.length})
            </h3>

            {loading ? (
              <div className="py-20 text-center text-slate-400 text-xs font-semibold">
                Loading categories desk...
              </div>
            ) : categories.length === 0 ? (
              <div className="py-20 text-center text-slate-400 text-xs">
                No active categories found in database.
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {categories.map((cat) => {
                  const isEditing = editingId === cat.id;

                  return (
                    <div 
                      key={cat.id}
                      className={`border rounded-2xl p-4 flex flex-col justify-between gap-4 transition-all duration-200 ${
                        isEditing 
                          ? 'border-emerald-500 bg-emerald-50/10 shadow-sm' 
                          : 'border-slate-100 hover:border-slate-200 hover:shadow-sm'
                      }`}
                    >
                      {/* Editing state */}
                      {isEditing ? (
                        <div className="space-y-3 text-xs">
                          <div className="flex gap-3 items-center">
                            <div className="w-12 h-12 rounded-full overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 relative group">
                              <img src={editPreview} className="w-8 h-8 object-contain" />
                              <label className="absolute inset-0 bg-black/60 text-white flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">
                                <Upload className="w-3.5 h-3.5" />
                                <input type="file" onChange={handleEditFileChange} accept="image/*" className="hidden" />
                              </label>
                            </div>
                            <div className="flex-1 space-y-1">
                              <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wide">ID: {cat.id}</span>
                              <input 
                                type="text"
                                value={editName}
                                onChange={(e) => setEditName(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-800 focus:outline-none focus:border-emerald-500"
                                placeholder="English Name"
                              />
                            </div>
                          </div>
                          <div className="space-y-1">
                            <input 
                              type="text"
                              value={editUrdu}
                              onChange={(e) => setEditUrdu(e.target.value)}
                              className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-800 text-right focus:outline-none focus:border-emerald-500"
                              placeholder="اردو نام"
                              style={{ fontFamily: 'var(--font-urdu)' }}
                            />
                          </div>
                          
                          <div className="flex gap-2 justify-end pt-2 border-t border-slate-100">
                            <button
                              onClick={handleCancelEdit}
                              disabled={submitting}
                              className="px-3 py-1.5 bg-slate-100 hover:bg-slate-250 text-slate-650 rounded-lg font-bold flex items-center gap-1 transition-colors"
                            >
                              <X className="w-3.5 h-3.5" /> Cancel
                            </button>
                            <button
                              onClick={() => handleSaveEdit(cat.id)}
                              disabled={submitting || !editName.trim()}
                              className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold flex items-center gap-1 transition-colors shadow-sm"
                            >
                              <Save className="w-3.5 h-3.5" /> Save
                            </button>
                          </div>
                        </div>
                      ) : (
                        // Normal display state
                        <>
                          <div className="flex items-start justify-between gap-2">
                            <div className="flex items-center gap-3">
                              <div className="w-12 h-12 rounded-full overflow-hidden border border-slate-100 bg-slate-50 flex items-center justify-center flex-shrink-0 shadow-inner">
                                <img src={cat.image} alt={cat.name} className="w-8 h-8 object-contain" />
                              </div>
                              <div className="space-y-0.5">
                                <h4 className="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight capitalize">
                                  {cat.name}
                                </h4>
                                <span className="text-[10px] text-slate-400 font-medium font-mono">
                                  Tag: {cat.id}
                                </span>
                              </div>
                            </div>
                            <span 
                              className="text-[11px] sm:text-xs text-slate-505 font-bold"
                              style={{ fontFamily: 'var(--font-urdu)' }}
                            >
                              {cat.urdu}
                            </span>
                          </div>

                          <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span className="text-[10px] text-slate-400 font-semibold leading-none">
                              Active Category
                            </span>
                            <div className="flex items-center gap-2">
                              <button
                                onClick={() => handleStartEdit(cat)}
                                className="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 hover:text-emerald-600 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg transition-colors"
                              >
                                <Edit2 className="w-3 h-3" /> Edit
                              </button>
                              <button
                                onClick={() => handleDeleteCategory(cat.id, cat.name)}
                                className="inline-flex items-center gap-1 text-[10px] font-bold text-rose-500 hover:bg-rose-50 border border-transparent px-2 py-1 rounded-lg transition-colors"
                              >
                                <Trash2 className="w-3 h-3" /> Delete
                              </button>
                            </div>
                          </div>
                        </>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Add Category Form (1/3 width) */}
        <div className="space-y-6">
          <div className="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm">
            <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-5 flex items-center gap-2">
              <Plus className="w-4.5 h-4.5 text-emerald-500" />
              Add New Category
            </h3>

            <form onSubmit={handleAddCategory} className="space-y-4 text-xs">
              {/* Category Logo/Icon select */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Category Icon</label>
                <div className="flex items-center gap-4">
                  <div className="w-14 h-14 rounded-full overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 shadow-inner relative group">
                    {newCatPreview ? (
                      <img src={newCatPreview} className="w-9 h-9 object-contain" />
                    ) : (
                      <Folder className="w-6 h-6 text-slate-300" />
                    )}
                  </div>
                  
                  <div className="flex-1">
                    <label className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-250 text-slate-650 rounded-xl font-bold cursor-pointer transition-colors">
                      <Upload className="w-3.5 h-3.5" /> Choose Icon File
                      <input 
                        type="file" 
                        onChange={handleNewFileChange} 
                        accept="image/*" 
                        className="hidden" 
                      />
                    </label>
                    <p className="text-[9px] text-slate-400 mt-1">Upload transparent PNG or SVG icon.</p>
                  </div>
                </div>
              </div>

              {/* English Name */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Category Name (English)</label>
                <input 
                  type="text" 
                  value={newCatName}
                  onChange={(e) => setNewCatName(e.target.value)}
                  placeholder="e.g. Snacks, Spices, Bakery"
                  required
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                />
              </div>

              {/* Urdu Name */}
              <div className="space-y-1.5">
                <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Category Name (Urdu)</label>
                <input 
                  type="text" 
                  value={newCatUrdu}
                  onChange={(e) => setNewCatUrdu(e.target.value)}
                  placeholder="مثال: بسکٹ، مصالحہ جات"
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-800 text-right focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
                  style={{ fontFamily: 'var(--font-urdu)' }}
                />
              </div>

              {/* Submit btn */}
              <button
                type="submit"
                disabled={submitting || !newCatName.trim()}
                className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-2.5 rounded-xl transition-all shadow-md active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-1.5 text-xs"
              >
                {submitting ? 'Adding Category...' : (
                  <>
                    <Plus className="w-4.5 h-4.5" /> Add Category
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
