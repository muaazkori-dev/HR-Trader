'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Plus, 
  Search, 
  Edit3, 
  Trash2, 
  Image as ImageIcon, 
  AlertTriangle,
  Download,
  Trash,
  X,
  FileCheck,
  ChevronDown,
  BarChart4
} from 'lucide-react';

interface Product {
  id: number;
  barcode: string;
  name: string;
  description: string;
  price: number;
  purchase_price: number;
  stock_quantity: number;
  weight?: string;
  unit: string;
  category: string;
  image: string;
  created_at: string;
}

interface InventoryContentProps {
  initialProducts: Product[];
}

const CATEGORIES: Record<string, string> = {
  anaj: 'Grains & Rice',
  shampoo: 'Hair Care',
  soap: 'Soaps & Care',
  cold_drinks: 'Beverages',
  water: 'Mineral Water',
  ice_cream: 'Ice Creams',
  milk: 'Dairy & Milk',
};

export const InventoryContent: React.FC<InventoryContentProps> = ({ initialProducts }) => {
  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [searchQuery, setSearchQuery] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  
  // Modals state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'add' | 'edit'>('add');
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

  // Form states
  const [barcode, setBarcode] = useState('');
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState(0);
  const [purchasePrice, setPurchasePrice] = useState(0);
  const [stockQuantity, setStockQuantity] = useState(0);
  const [weight, setWeight] = useState('');
  const [unit, setUnit] = useState('pcs');
  const [category, setCategory] = useState('anaj');
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [imageUrl, setImageUrl] = useState('');
  
  // Bulk state
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Filter products
  const filteredProducts = products.filter((p) => {
    const matchesSearch =
      searchQuery.trim() === '' ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.barcode.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory =
      categoryFilter === '' || p.category === categoryFilter;
    return matchesSearch && matchesCategory;
  });

  const openAddModal = () => {
    setModalMode('add');
    setSelectedProduct(null);
    setBarcode('');
    setName('');
    setDescription('');
    setPrice(0);
    setPurchasePrice(0);
    setStockQuantity(0);
    setWeight('');
    setUnit('pcs');
    setCategory('anaj');
    setImageFile(null);
    setImageUrl('');
    setMessage('');
    setIsModalOpen(true);
  };

  const openEditModal = (p: Product) => {
    setModalMode('edit');
    setSelectedProduct(p);
    setBarcode(p.barcode);
    setName(p.name);
    setDescription(p.description || '');
    setPrice(p.price);
    setPurchasePrice(p.purchase_price);
    setStockQuantity(p.stock_quantity);
    setWeight(p.weight || '');
    setUnit(p.unit);
    setCategory(p.category);
    setImageFile(null);
    setImageUrl(p.image || '');
    setMessage('');
    setIsModalOpen(true);
  };

  // Upload image to Supabase Storage bucket
  const uploadImage = async (file: File): Promise<string> => {
    const fileExt = file.name.split('.').pop();
    const fileName = `${Math.random().toString(36).substring(2)}.${fileExt}`;
    const filePath = `products/${fileName}`;

    // Upload
    const { error: uploadError } = await supabase.storage
      .from('product-images')
      .upload(filePath, file, { cacheControl: '3600', upsert: true });

    if (uploadError) {
      throw uploadError;
    }

    // Get Public URL
    const { data } = supabase.storage
      .from('product-images')
      .getPublicUrl(filePath);

    return data.publicUrl;
  };

  const handleSaveProduct = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setMessage('');
    setIsError(false);

    try {
      let finalImageUrl = imageUrl;

      // 1. Upload image if selected
      if (imageFile) {
        finalImageUrl = await uploadImage(imageFile);
      }

      const payload = {
        barcode: barcode.trim(),
        name: name.trim(),
        description: description.trim() || null,
        price,
        purchase_price: purchasePrice,
        stock_quantity: stockQuantity,
        weight: weight.trim() || null,
        unit,
        category,
        image: finalImageUrl,
      };

      if (modalMode === 'add') {
        // Insert product
        const { data, error } = await supabase
          .from('products')
          .insert([payload])
          .select()
          .single();

        if (error) throw error;
        if (data) {
          setProducts([data as Product, ...products]);
          setSuccess('Product successfully added to inventory catalog!');
        }
      } else {
        // Update product
        if (!selectedProduct) return;
        const { data, error } = await supabase
          .from('products')
          .update(payload)
          .eq('id', selectedProduct.id)
          .select()
          .single();

        if (error) throw error;
        if (data) {
          setProducts(products.map((p) => (p.id === selectedProduct.id ? (data as Product) : p)));
          setSuccess('Product details successfully updated in inventory register.');
        }
      }
    } catch (err: any) {
      setIsError(true);
      setMessage(err.message || 'Failed to save product. Check database connections.');
    } finally {
      setSubmitting(false);
    }
  };

  const setSuccess = (msg: string) => {
    setIsError(false);
    setMessage(msg);
    setTimeout(() => {
      setIsModalOpen(false);
    }, 1500);
  };

  const handleDeleteProduct = async (id: number) => {
    if (!confirm('Remove product completely from registry? This cannot be undone.')) return;

    try {
      const { error } = await supabase
        .from('products')
        .delete()
        .eq('id', id);

      if (error) throw error;
      setProducts(products.filter((p) => p.id !== id));
      setSelectedIds(selectedIds.filter((selId) => selId !== id));
    } catch (err: any) {
      alert('Delete failed: ' + err.message);
    }
  };

  // Bulk selectors
  const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelectedIds(filteredProducts.map((p) => p.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelectOne = (id: number) => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter((selId) => selId !== id));
    } else {
      setSelectedIds([...selectedIds, id]);
    }
  };

  const handleBulkDelete = async () => {
    if (selectedIds.length === 0) return;
    if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected products?`)) return;

    try {
      const { error } = await supabase
        .from('products')
        .delete()
        .in('id', selectedIds);

      if (error) throw error;
      setProducts(products.filter((p) => !selectedIds.includes(p.id)));
      setSelectedIds([]);
      alert('Selected products successfully deleted.');
    } catch (err: any) {
      alert('Bulk delete failed: ' + err.message);
    }
  };

  const handleBulkExport = () => {
    if (selectedIds.length === 0) return;
    const itemsToExport = products.filter((p) => selectedIds.includes(p.id));

    // Convert to JSON and trigger download
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(itemsToExport, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute('href', dataStr);
    downloadAnchor.setAttribute('download', 'hr_traders_inventory_export.json');
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  };

  return (
    <div className="space-y-6 w-full flex flex-col flex-1">
      
      {/* Overview stats panel */}
      <section className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div className="text-left">
          <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">Inventory Catalog</h1>
          <p className="text-xs text-slate-400 mt-1">Manage catalog register, stock levels, barcoding, and image assets.</p>
        </div>
        <button
          onClick={openAddModal}
          className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center gap-1.5 active:scale-95 transition-all self-start sm:self-center"
        >
          <Plus className="w-4.5 h-4.5" /> Add New Product
        </button>
      </section>

      {/* Control row (Search & category filters) */}
      <section className="flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          {/* Search */}
          <div className="relative w-full sm:w-64">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search name, barcode..."
              className="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 text-slate-800 shadow-sm"
            />
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          </div>

          {/* Category Filter */}
          <select
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
            className="bg-white border border-slate-200 px-3.5 py-2 rounded-xl text-xs text-slate-650 focus:outline-none focus:border-emerald-500 shadow-sm w-full sm:w-auto font-semibold"
          >
            <option value="">All Categories</option>
            {Object.entries(CATEGORIES).map(([key, name]) => (
              <option key={key} value={key}>{name}</option>
            ))}
          </select>
        </div>

        {/* Bulk Action Controls */}
        {selectedIds.length > 0 && (
          <div className="flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-xl p-1.5 shadow-inner">
            <span className="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider px-2 border-r border-slate-200">
              {selectedIds.length} Selected
            </span>
            <button
              onClick={handleBulkExport}
              className="p-1.5 text-slate-600 hover:text-emerald-600 rounded-lg hover:bg-white transition-all flex items-center gap-1 text-[10px] font-bold"
              title="Export Selected"
            >
              <Download className="w-3.5 h-3.5" /> Export
            </button>
            <button
              onClick={handleBulkDelete}
              className="p-1.5 text-slate-650 hover:text-rose-600 rounded-lg hover:bg-white transition-all flex items-center gap-1 text-[10px] font-bold"
              title="Delete Selected"
            >
              <Trash className="w-3.5 h-3.5" /> Delete
            </button>
          </div>
        )}
      </section>

      {/* Catalog Table */}
      <section className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm flex-1 flex flex-col justify-between">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-xs">
            <thead className="bg-slate-50 text-slate-400 font-extrabold border-b border-slate-200 uppercase tracking-wider text-[10px]">
              <tr>
                <th className="p-4 text-center w-16">
                  <input
                    type="checkbox"
                    onChange={handleSelectAll}
                    checked={filteredProducts.length > 0 && selectedIds.length === filteredProducts.length}
                    className="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500"
                  />
                </th>
                <th className="p-4 w-28">Barcode</th>
                <th className="p-4">Product Details</th>
                <th className="p-4">Category</th>
                <th className="p-4 text-right">Purchase Cost</th>
                <th className="p-4 text-right">Selling Price</th>
                <th className="p-4 text-center">Unit / Weight</th>
                <th className="p-4 text-center w-24">Stock Level</th>
                <th className="p-4 text-center w-28 pr-6">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700">
              {filteredProducts.length === 0 ? (
                <tr>
                  <td colSpan={9} className="p-16 text-center text-slate-400 font-semibold">
                    No products matching search queries or filters found in inventory catalog.
                  </td>
                </tr>
              ) : (
                filteredProducts.map((p) => {
                  const isLow = p.stock_quantity <= 5;
                  const isSelected = selectedIds.includes(p.id);

                  return (
                    <tr key={p.id} className={`hover:bg-slate-50/50 transition-colors ${isSelected ? 'bg-emerald-50/10' : ''}`}>
                      <td className="p-4 text-center">
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => handleSelectOne(p.id)}
                          className="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500"
                        />
                      </td>
                      <td className="p-4 font-mono font-bold text-slate-500">{p.barcode}</td>
                      <td className="p-4">
                        <div className="flex items-center gap-3">
                          <img
                            src={p.image ? p.image : '/assets/images/placeholder.svg'}
                            alt={p.name}
                            className="w-10 h-10 object-cover rounded-xl border border-slate-200 bg-slate-50 flex-shrink-0"
                          />
                          <div className="text-left">
                            <strong className="text-slate-800 text-xs block font-bold leading-tight">{p.name}</strong>
                            <span className="text-[10px] text-slate-400 block truncate max-w-[200px] mt-0.5" title={p.description}>
                              {p.description || 'No description added'}
                            </span>
                          </div>
                        </div>
                      </td>
                      <td className="p-4 font-semibold text-slate-650 capitalize">
                        {CATEGORIES[p.category] || p.category.replace('_', ' ')}
                      </td>
                      <td className="p-4 text-right font-mono font-bold text-slate-500">Rs. {p.purchase_price.toFixed(0)}</td>
                      <td className="p-4 text-right font-mono font-black text-emerald-600">Rs. {p.price.toFixed(0)}</td>
                      <td className="p-4 text-center font-bold text-slate-650">
                        {p.weight ? `${p.weight} (${p.unit})` : p.unit}
                      </td>
                      <td className="p-4 text-center">
                        <span className={`px-2 py-0.5 rounded font-mono font-bold text-[10px] border ${
                          isLow 
                            ? 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse' 
                            : 'bg-slate-100 text-slate-700 border-slate-200'
                        }`}>
                          {p.stock_quantity}
                        </span>
                      </td>
                      <td className="p-4 text-center pr-6 whitespace-nowrap">
                        <div className="flex items-center justify-center gap-1.5">
                          <button
                            onClick={() => openEditModal(p)}
                            className="p-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 border border-slate-250 rounded-lg transition-all"
                            title="Edit product details"
                          >
                            <Edit3 className="w-3.5 h-3.5" />
                          </button>
                          <button
                            onClick={() => handleDeleteProduct(p.id)}
                            className="p-1.5 bg-slate-100 text-slate-650 hover:bg-rose-50 hover:text-rose-600 border border-slate-250 rounded-lg transition-all"
                            title="Remove product"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </section>

      {/* 3. DYNAMIC ADD/EDIT FORM MODAL */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          {/* Overlay */}
          <div onClick={() => setIsModalOpen(false)} className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" />
          
          {/* Content Card */}
          <div className="relative bg-white border border-slate-200 rounded-3xl w-full max-w-2xl shadow-2xl flex flex-col z-10 max-h-[90vh] overflow-y-auto animate-in zoom-in duration-200">
            {/* Header */}
            <div className="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
              <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-2">
                <BarChart4 className="w-4.5 h-4.5 text-emerald-600" />
                {modalMode === 'add' ? 'Register New Product' : 'Modify Product Registry'}
              </h3>
              <button
                onClick={() => setIsModalOpen(false)}
                className="p-1.5 hover:bg-slate-200 rounded-lg transition-colors text-slate-400 hover:text-slate-700"
              >
                <X className="w-4.5 h-4.5" />
              </button>
            </div>

            {/* Form */}
            <form onSubmit={handleSaveProduct} className="p-5 space-y-4 text-xs text-left">
              
              {/* Feedback messages */}
              {message && (
                <div className={`p-3.5 rounded-xl border font-semibold leading-relaxed ${
                  isError 
                    ? 'bg-rose-50 text-rose-700 border-rose-200' 
                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                }`}>
                  {message}
                </div>
              )}

              {/* Grid 1: Name, Barcode */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Product Title / Name</label>
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    placeholder="Enter product title"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Barcode Reference</label>
                  <input
                    type="text"
                    value={barcode}
                    onChange={(e) => setBarcode(e.target.value)}
                    required
                    placeholder="Scan or type barcode"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
              </div>

              {/* Description */}
              <div className="space-y-1">
                <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Product Description</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={2}
                  placeholder="Detail ingredients, organic sources, or usage guidelines..."
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 resize-none font-medium"
                />
              </div>

              {/* Grid 2: Pricing, Cost */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Purchase Price (Cost)</label>
                  <input
                    type="number"
                    value={purchasePrice || ''}
                    onChange={(e) => setPurchasePrice(parseFloat(e.target.value) || 0)}
                    required
                    min={0}
                    placeholder="0"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Selling Price</label>
                  <input
                    type="number"
                    value={price || ''}
                    onChange={(e) => setPrice(parseFloat(e.target.value) || 0)}
                    required
                    min={0}
                    placeholder="0"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Stock Register</label>
                  <input
                    type="number"
                    value={stockQuantity || ''}
                    onChange={(e) => setStockQuantity(parseInt(e.target.value, 10) || 0)}
                    required
                    min={0}
                    placeholder="0"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
              </div>

              {/* Grid 3: Category, Weight, Unit */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Category Category</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value)}
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-bold"
                  >
                    {Object.entries(CATEGORIES).map(([key, name]) => (
                      <option key={key} value={key}>{name}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Weight / Vol</label>
                  <input
                    type="text"
                    value={weight}
                    onChange={(e) => setWeight(e.target.value)}
                    placeholder="e.g. 1 kg, 250 ml"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Unit Measurement</label>
                  <select
                    value={unit}
                    onChange={(e) => setUnit(e.target.value)}
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-bold"
                  >
                    <option value="pcs">Pcs (Individual)</option>
                    <option value="kg">Kg (Kilograms)</option>
                    <option value="pack">Pack (Box/Bundle)</option>
                    <option value="litre">Litre (Liquid)</option>
                  </select>
                </div>
              </div>

              {/* Image upload widget */}
              <div className="space-y-1">
                <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Product Image Asset</label>
                <div className="flex items-center gap-4 p-4 bg-slate-50 border border-slate-250 border-dashed rounded-2xl">
                  {imageUrl ? (
                    <img
                      src={imageUrl}
                      alt="Preview"
                      className="w-16 h-16 object-cover rounded-xl border border-slate-200 bg-white"
                    />
                  ) : (
                    <div className="w-16 h-16 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-350">
                      <ImageIcon className="w-6 h-6" />
                    </div>
                  )}
                  <div className="flex-1 space-y-1">
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) {
                          setImageFile(file);
                          setImageUrl(URL.createObjectURL(file));
                        }
                      }}
                      className="text-xs text-slate-550 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border file:border-slate-250 file:text-[10px] file:font-bold file:bg-white file:text-slate-700 hover:file:bg-slate-100 cursor-pointer"
                    />
                    <p className="text-[10px] text-slate-405 font-normal">Choose images (Max 10MB). Image uploads directly to Supabase cloud CDN bucket.</p>
                  </div>
                </div>
              </div>

              {/* Submit Buttons */}
              <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold rounded-xl transition-all shadow-sm"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md disabled:bg-slate-300"
                >
                  {submitting ? 'Saving Register...' : 'Save Product Record'}
                </button>
              </div>

            </form>
          </div>
        </div>
      )}

    </div>
  );
};
