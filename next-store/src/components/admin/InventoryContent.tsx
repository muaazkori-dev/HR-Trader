'use client';

import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { supabase } from '@/lib/supabase';
import { getProductImageUrl } from '@/lib/utils';
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
  BarChart4,
  Link2,
  Layers,
  Check,
  Sparkles,
  Unlink
} from 'lucide-react';

export interface VariantGroup {
  id: string;
  name?: string;
  product_ids: number[];
}

interface Product {
  id: number;
  barcode: string;
  name: string;
  description: string;
  price: number;
  purchase_price: number;
  old_price?: number | null;
  discount_percentage?: number | null;
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

const DEFAULT_CATEGORIES: Record<string, string> = {
  anaj: 'Grains & Rice',
  shampoo: 'Hair Care',
  soap: 'Soaps & Care',
  cold_drinks: 'Beverages',
  water: 'Mineral Water',
  ice_cream: 'Ice Creams',
  milk: 'Dairy & Milk',
};

export const InventoryContent: React.FC<InventoryContentProps> = ({ initialProducts }) => {
  const searchParams = useSearchParams();
  const editIdParam = searchParams.get('editId');
  const [hasAutoOpened, setHasAutoOpened] = useState(false);

  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [categoryList, setCategoryList] = useState<{ id: string; name: string }[]>([
    { id: 'anaj', name: 'BAKING AND COOKING' },
    { id: 'ice_cream', name: 'ICE CREAM' },
    { id: 'beverages', name: 'BEVERAGE' },
    { id: 'milk', name: 'HERBAL AND NUTRITION' },
    { id: 'cosmetics', name: 'COSMETICS' },
    { id: 'confectionary', name: 'SNACKS AND CHIPS' },
    { id: 'bakery', name: 'DAIRY AND BREAK FAST' },
    { id: 'sauce', name: 'PASTA AND SAUCES' },
    { id: 'stationary', name: 'STATIONARY' },
    { id: 'confectionary_8864', name: 'CONFECTIONARY' },
    { id: 'household_and_laundry', name: 'HOUSEHOLD AND LAUNDRY' },
  ]);

  const getCategoryName = (key: string) => {
    const found = categoryList.find(c => c.id === key);
    if (found) return found.name;
    const num = parseInt(key, 10);
    if (!isNaN(num) && categoryList[num]) {
      return categoryList[num].name;
    }
    return key ? key.replace(/_/g, ' ') : '';
  };

  useEffect(() => {
    const fetchDbCategories = async () => {
      try {
        const { data, error } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'store_categories')
          .maybeSingle();

        if (!error && data?.val_value) {
          const parsed = JSON.parse(data.val_value);
          if (Array.isArray(parsed)) {
            setCategoryList(parsed.map((c: any) => ({
              id: c.id || c.key,
              name: c.name || c.id
            })));
          } else if (parsed && typeof parsed === 'object') {
            setCategoryList(Object.entries(parsed).map(([id, val]: any) => ({
              id,
              name: val.name || id
            })));
          }
        }
      } catch (err) {
        console.error('Error loading db categories:', err);
      }
    };

    const fetchVariantGroups = async () => {
      try {
        const { data, error } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'product_variant_groups')
          .maybeSingle();

        if (!error && data?.val_value) {
          const parsed = JSON.parse(data.val_value);
          if (Array.isArray(parsed)) {
            setVariantGroups(parsed);
          } else if (parsed && typeof parsed === 'object') {
            setVariantGroups(Object.values(parsed));
          }
        }
      } catch (err) {
        console.error('Error loading variant groups:', err);
      }
    };

    fetchDbCategories();
    fetchVariantGroups();
  }, []);

  // Variant Groups State
  const [variantGroups, setVariantGroups] = useState<VariantGroup[]>([]);
  const [combineModalProduct, setCombineModalProduct] = useState<Product | null>(null);
  const [selectedProductIdsToCombine, setSelectedProductIdsToCombine] = useState<number[]>([]);
  const [searchCombineQuery, setSearchCombineQuery] = useState('');
  const [savingGroup, setSavingGroup] = useState(false);

  const extractBrandKeywords = (productName: string): string => {
    if (!productName) return '';
    const cleaned = productName
      .replace(/\b\d+(\.\d+)?\s*(ml|ltr|litre|liter|kg|gm|g|gram|grams|pcs|pc|pack|pouch|tin|can|box|bottle|tablet|tablets|sachet|sachets)\b/gi, '')
      .replace(/[\(\)\[\]\-–\/\\,\.]/g, ' ')
      .trim();
    const words = cleaned.split(/\s+/).filter(w => w.length > 1);
    return words[0] || '';
  };

  const getGroupForProduct = (productId: number): VariantGroup | undefined => {
    return variantGroups.find(g => Array.isArray(g.product_ids) && g.product_ids.includes(productId));
  };

  const getVariantCountForProduct = (productId: number): number => {
    const group = getGroupForProduct(productId);
    return group ? group.product_ids.length : 0;
  };

  const getSuggestedSiblings = (targetProduct: Product, currentSelectedIds: number[]): Product[] => {
    const brand = extractBrandKeywords(targetProduct.name).toLowerCase();
    if (!brand || brand.length < 3) return [];
    
    return products.filter(p => {
      if (p.id === targetProduct.id) return false;
      if (currentSelectedIds.includes(p.id)) return false;
      
      const pBrand = extractBrandKeywords(p.name).toLowerCase();
      const matches = p.name.toLowerCase().includes(brand) || (pBrand && pBrand === brand);
      return matches;
    }).slice(0, 12);
  };

  const openCombineModal = (p: Product) => {
    setCombineModalProduct(p);
    const group = getGroupForProduct(p.id);
    if (group) {
      setSelectedProductIdsToCombine(group.product_ids.filter(id => id !== p.id));
    } else {
      setSelectedProductIdsToCombine([]);
    }
    setSearchCombineQuery('');
  };

  const handleToggleProductInCombine = (productId: number) => {
    setSelectedProductIdsToCombine(prev => 
      prev.includes(productId) ? prev.filter(id => id !== productId) : [...prev, productId]
    );
  };

  const handleRemoveProductFromCombine = (productId: number) => {
    setSelectedProductIdsToCombine(prev => prev.filter(id => id !== productId));
  };

  const handleAddAllSuggested = (suggested: Product[]) => {
    const idsToAdd = suggested.map(s => s.id);
    setSelectedProductIdsToCombine(prev => Array.from(new Set([...prev, ...idsToAdd])));
  };

  const handleSaveCombineGroup = async () => {
    if (!combineModalProduct) return;
    setSavingGroup(true);

    try {
      const currentId = combineModalProduct.id;
      const allSelectedIds = Array.from(new Set([currentId, ...selectedProductIdsToCombine]));

      let updatedGroups = [...variantGroups];

      if (allSelectedIds.length <= 1) {
        // User unlinked all or only 1 left -> remove from groups
        updatedGroups = updatedGroups
          .map(g => ({
            ...g,
            product_ids: g.product_ids.filter(id => id !== currentId)
          }))
          .filter(g => g.product_ids.length > 1);
      } else {
        // Auto-merge: Find all existing groups that contain any of allSelectedIds
        const intersectingGroups = updatedGroups.filter(g =>
          g.product_ids.some(id => allSelectedIds.includes(id))
        );

        const mergedIds = Array.from(
          new Set([
            ...allSelectedIds,
            ...intersectingGroups.flatMap(g => g.product_ids)
          ])
        );

        // Remove old intersecting groups
        updatedGroups = updatedGroups.filter(
          g => !intersectingGroups.some(ig => ig.id === g.id)
        );

        const existingGroupId = intersectingGroups[0]?.id;
        const newGroupId = existingGroupId || `group_${Date.now()}_${Math.random().toString(36).substring(2, 6)}`;

        updatedGroups.push({
          id: newGroupId,
          name: combineModalProduct.name,
          product_ids: mergedIds
        });
      }

      const { error } = await supabase
        .from('settings')
        .upsert({
          key_name: 'product_variant_groups',
          val_value: JSON.stringify(updatedGroups)
        }, { onConflict: 'key_name' });

      if (error) throw error;

      setVariantGroups(updatedGroups);
      setCombineModalProduct(null);
    } catch (err: any) {
      console.error('Error saving variant group:', err);
      alert('Failed to save variants link: ' + (err?.message || 'Unknown error'));
    } finally {
      setSavingGroup(false);
    }
  };
  const [searchQuery, setSearchQuery] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  
  // Modals state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'add' | 'edit'>('add');
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

  // Form states
  const [barcode, setBarcode] = useState('');

  // Auto-open modal if editId is provided in URL
  useEffect(() => {
    if (editIdParam && !hasAutoOpened && products.length > 0) {
      const match = products.find(p => p.id === parseInt(editIdParam, 10));
      if (match) {
        setHasAutoOpened(true);
        // Call next tick to make sure DOM / component has completely finished mount loading
        setTimeout(() => {
          openEditModal(match);
        }, 100);
      }
    }
  }, [editIdParam, products, hasAutoOpened]);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState<string>('');
  const [purchasePrice, setPurchasePrice] = useState<string>('');
  const [oldPrice, setOldPrice] = useState<string>('');
  const [discountPercentage, setDiscountPercentage] = useState<string>('');
  const [stockQuantity, setStockQuantity] = useState<string>('');
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
      categoryFilter === '' ||
      p.category === categoryFilter ||
      (categoryList.findIndex(c => c.id === categoryFilter) !== -1 &&
        p.category === String(categoryList.findIndex(c => c.id === categoryFilter)));
    return matchesSearch && matchesCategory;
  });

  const openAddModal = () => {
    setModalMode('add');
    setSelectedProduct(null);
    setBarcode('');
    setName('');
    setDescription('');
    setPrice('');
    setPurchasePrice('');
    setOldPrice('');
    setDiscountPercentage('');
    setStockQuantity('');
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
    setPrice(p.price !== undefined && p.price !== null ? String(p.price) : '');
    setPurchasePrice(p.purchase_price !== undefined && p.purchase_price !== null ? String(p.purchase_price) : '');
    setOldPrice(p.old_price !== undefined && p.old_price !== null ? String(p.old_price) : '');
    setDiscountPercentage(p.discount_percentage !== undefined && p.discount_percentage !== null ? String(p.discount_percentage) : '');
    setStockQuantity(p.stock_quantity !== undefined && p.stock_quantity !== null ? String(p.stock_quantity) : '');
    setWeight(p.weight || '');
    setUnit(p.unit);
    setCategory(p.category);
    setImageFile(null);
    setImageUrl(p.image ? getProductImageUrl(p.image) : '');
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

      let numericPrice = price === '' ? 0 : parseFloat(price);
      let calculatedOldPrice = oldPrice === '' ? null : parseFloat(oldPrice);
      let calculatedDiscountPercent = discountPercentage === '' ? null : parseInt(discountPercentage, 10);

      if (calculatedOldPrice !== null && calculatedOldPrice > numericPrice && !calculatedDiscountPercent) {
        calculatedDiscountPercent = Math.round(((calculatedOldPrice - numericPrice) / calculatedOldPrice) * 100);
      } else if (calculatedDiscountPercent && calculatedDiscountPercent > 0 && (!calculatedOldPrice || calculatedOldPrice === 0)) {
        calculatedOldPrice = parseFloat((numericPrice / (1 - (calculatedDiscountPercent / 100))).toFixed(2));
      }

      const payload = {
        barcode: barcode.trim(),
        name: name.trim(),
        description: description.trim() || null,
        price: numericPrice,
        purchase_price: purchasePrice === '' ? 0 : parseFloat(purchasePrice),
        old_price: calculatedOldPrice,
        discount_percentage: calculatedDiscountPercent,
        stock_quantity: stockQuantity === '' ? 0 : parseInt(stockQuantity, 10),
        weight: weight.trim() || null,
        unit,
        category,
        image: finalImageUrl,
      };

      if (modalMode === 'add') {
        // Fetch current maximum product ID to prevent out-of-sync sequence errors
        const { data: maxIdData } = await supabase
          .from('products')
          .select('id')
          .order('id', { ascending: false })
          .limit(1);

        const nextId = maxIdData && maxIdData.length > 0 ? maxIdData[0].id + 1 : 1;
        const addPayload = { ...payload, id: nextId };

        // Insert product
        const { data, error } = await supabase
          .from('products')
          .insert([addPayload])
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
            {categoryList.map((cat) => (
              <option key={cat.id} value={cat.id}>{cat.name}</option>
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
                  const variantCount = getVariantCountForProduct(p.id);

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
                            src={getProductImageUrl(p.image)}
                            alt={p.name}
                            className="w-10 h-10 object-contain rounded-xl border border-slate-200 bg-white flex-shrink-0 p-0.5"
                          />
                          <div className="text-left">
                            <strong className="text-slate-800 text-xs block font-bold leading-tight">{p.name}</strong>
                            <span className="text-[10px] text-slate-400 block truncate max-w-[200px] mt-0.5" title={p.description}>
                              {p.description || 'No description added'}
                            </span>
                            {variantCount > 1 && (
                              <span className="inline-flex items-center gap-1 mt-1 text-[9px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md">
                                <Layers className="w-2.5 h-2.5 text-emerald-600" /> {variantCount} Flavours / Sizes Linked
                              </span>
                            )}
                          </div>
                        </div>
                      </td>
                      <td className="p-4 font-semibold text-slate-650 capitalize">
                        {getCategoryName(p.category)}
                      </td>
                      <td className="p-4 text-right font-mono font-bold text-slate-500">
                        {p.purchase_price > 0 ? `Rs. ${p.purchase_price.toFixed(0)}` : '-'}
                      </td>
                      <td className="p-4 text-right font-mono font-black text-emerald-600">
                        {p.price > 0 ? `Rs. ${p.price.toFixed(0)}` : '-'}
                      </td>
                      <td className="p-4 text-center font-bold text-slate-650">
                        {p.weight ? `${p.weight} (${p.unit})` : p.unit}
                      </td>
                      <td className="p-4 text-center">
                        {p.stock_quantity > 0 ? (
                          <span className={`px-2 py-0.5 rounded font-mono font-bold text-[10px] border ${
                            isLow 
                              ? 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse' 
                              : 'bg-slate-100 text-slate-700 border-slate-200'
                          }`}>
                            {p.stock_quantity}
                          </span>
                        ) : (
                          <span className="text-slate-350 font-semibold text-[10px]">-</span>
                        )}
                      </td>
                      <td className="p-4 text-center pr-6 whitespace-nowrap">
                        <div className="flex items-center justify-center gap-1.5">
                          <button
                            type="button"
                            onClick={() => openCombineModal(p)}
                            className={`p-1.5 rounded-lg border transition-all flex items-center gap-1 text-[10px] font-bold ${
                              variantCount > 1 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 shadow-xs' 
                                : 'bg-slate-100 text-slate-600 border-slate-250 hover:bg-slate-200 hover:text-slate-900'
                            }`}
                            title="Combine / Link Flavours & Sizes (فلیور اور سائز جوڑیں)"
                          >
                            <Link2 className="w-3.5 h-3.5 text-emerald-600" />
                            <span>{variantCount > 1 ? `${variantCount}` : 'Link'}</span>
                          </button>
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

              {/* Row 1: Purchase Price & Selling Price */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Purchase Price (Cost)</label>
                  <input
                    type="number"
                    value={purchasePrice}
                    onChange={(e) => setPurchasePrice(e.target.value)}
                    min={0}
                    placeholder="0.00"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Selling Price</label>
                  <input
                    type="number"
                    value={price}
                    onChange={(e) => setPrice(e.target.value)}
                    min={0}
                    required
                    placeholder="e.g. 220"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
              </div>

              {/* Row 2: Original Price & Discount Percentage */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Original Price (Old Price)</label>
                  <input
                    type="number"
                    value={oldPrice}
                    onChange={(e) => setOldPrice(e.target.value)}
                    min={0}
                    placeholder="e.g. 1000.00"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Discount Percentage (%)</label>
                  <input
                    type="number"
                    value={discountPercentage}
                    onChange={(e) => setDiscountPercentage(e.target.value)}
                    min={0}
                    max={100}
                    placeholder="e.g. 15"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
              </div>

              {/* Row 3: Stock Quantity & Category */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Stock Quantity</label>
                  <input
                    type="number"
                    value={stockQuantity}
                    onChange={(e) => setStockQuantity(e.target.value)}
                    min={0}
                    placeholder="e.g. 50"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-mono font-bold"
                  />
                </div>
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Category</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value)}
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-bold text-left"
                  >
                    {categoryList.map((cat) => (
                      <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Row 4: Unit Weight & Unit */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Unit Weight (e.g. 1 kg)</label>
                  <input
                    type="text"
                    value={weight}
                    onChange={(e) => setWeight(e.target.value)}
                    placeholder="e.g. 1 kg, 250 ml"
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-semibold"
                  />
                </div>
                <div className="space-y-1 text-left">
                  <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Unit</label>
                  <select
                    value={unit}
                    onChange={(e) => setUnit(e.target.value)}
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 font-bold text-left"
                  >
                    <option value="pcs">Pcs (Individual)</option>
                    <option value="kg">kg (Kilograms)</option>
                    <option value="pack">pack (Box/Bundle)</option>
                    <option value="litre">litre (Liquid)</option>
                  </select>
                </div>
              </div>

              {/* Image upload widget */}
              <div className="space-y-1">
                <label className="block text-[9px] font-bold text-slate-405 uppercase tracking-wider">Product Image Asset</label>
                <div className="flex items-center gap-4 p-4 bg-slate-50 border border-slate-250 border-dashed rounded-2xl">
                  {imageUrl ? (
                    <img
                      src={imageUrl ? (imageUrl.startsWith('blob:') || imageUrl.startsWith('http') || imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl) : '/assets/images/placeholder.svg'}
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

      {/* 4. COMBINE VARIANTS / LINK FLAVOURS & SIZES MODAL */}
      {combineModalProduct && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          {/* Backdrop */}
          <div 
            onClick={() => !savingGroup && setCombineModalProduct(null)} 
            className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" 
          />
          
          <div className="relative bg-white border border-slate-200 rounded-3xl w-full max-w-3xl shadow-2xl flex flex-col z-10 max-h-[90vh] overflow-hidden animate-in zoom-in-95 duration-200">
            {/* Header */}
            <div className="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 shadow-sm">
                  <Link2 className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-black text-slate-800 text-sm tracking-tight flex items-center gap-2">
                    Combine Product Variants (فلیور اور سائز جوڑیں)
                  </h3>
                  <p className="text-[11px] text-slate-500 font-medium">
                    Pantene, Kisan Ghee ya kisi bhi product ke tamam flavours aur sizes ko ek sath link karein.
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => !savingGroup && setCombineModalProduct(null)}
                className="p-2 hover:bg-slate-200 rounded-xl transition-colors text-slate-400 hover:text-slate-700"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Scrollable Body */}
            <div className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-140px)] text-left">
              
              {/* Target Selected Product Header */}
              <div className="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between gap-4">
                <div className="flex items-center gap-3.5">
                  <img
                    src={getProductImageUrl(combineModalProduct.image)}
                    alt={combineModalProduct.name}
                    className="w-14 h-14 object-contain rounded-xl border border-emerald-200 bg-white p-1 shadow-sm flex-shrink-0"
                  />
                  <div>
                    <span className="text-[9px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full inline-block mb-1">
                      Main Selected Product
                    </span>
                    <h4 className="font-extrabold text-slate-900 text-sm leading-snug">
                      {combineModalProduct.name}
                    </h4>
                    <div className="flex items-center gap-3 text-xs text-slate-500 font-mono mt-1">
                      <span>Barcode: <strong className="text-slate-700">{combineModalProduct.barcode || 'N/A'}</strong></span>
                      <span>•</span>
                      <span className="text-emerald-700 font-black">Rs. {combineModalProduct.price}</span>
                      <span>•</span>
                      <span className="text-slate-600 font-semibold">{combineModalProduct.weight ? `${combineModalProduct.weight} (${combineModalProduct.unit})` : combineModalProduct.unit}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* SECTION 1: Currently Linked Variants */}
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Layers className="w-4 h-4 text-emerald-600" />
                    <h5 className="text-xs font-black text-slate-800 uppercase tracking-wider">
                      Currently Combined Variants ({selectedProductIdsToCombine.length + 1} Total)
                    </h5>
                  </div>
                  {selectedProductIdsToCombine.length > 0 && (
                    <button
                      type="button"
                      onClick={() => setSelectedProductIdsToCombine([])}
                      className="text-[10px] font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1 cursor-pointer"
                    >
                      <Unlink className="w-3 h-3" /> Unlink All
                    </button>
                  )}
                </div>

                {selectedProductIdsToCombine.length === 0 ? (
                  <div className="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                    Is product ke sath abhi koi doosra flavour ya size combine nahi hai. Neeche se auto-suggested ya search karke add karein.
                  </div>
                ) : (
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    {/* Main product badge */}
                    <div className="p-2.5 rounded-xl border border-emerald-300 bg-emerald-50/50 flex items-center justify-between">
                      <div className="flex items-center gap-2.5 overflow-hidden">
                        <img
                          src={getProductImageUrl(combineModalProduct.image)}
                          alt={combineModalProduct.name}
                          className="w-9 h-9 object-contain rounded-lg border border-emerald-200 bg-white p-0.5 flex-shrink-0"
                        />
                        <div className="truncate">
                          <span className="text-[11px] font-bold text-slate-800 block truncate">{combineModalProduct.name}</span>
                          <span className="text-[10px] text-emerald-700 font-bold">Rs. {combineModalProduct.price} • (Current)</span>
                        </div>
                      </div>
                      <span className="text-[9px] font-black uppercase text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">Active</span>
                    </div>

                    {/* Other linked products */}
                    {selectedProductIdsToCombine.map(id => {
                      const item = products.find(p => p.id === id);
                      if (!item) return null;
                      return (
                        <div key={item.id} className="p-2.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all flex items-center justify-between shadow-xs">
                          <div className="flex items-center gap-2.5 overflow-hidden">
                            <img
                              src={getProductImageUrl(item.image)}
                              alt={item.name}
                              className="w-9 h-9 object-contain rounded-lg border border-slate-100 bg-slate-50 p-0.5 flex-shrink-0"
                            />
                            <div className="truncate">
                              <span className="text-[11px] font-bold text-slate-800 block truncate" title={item.name}>{item.name}</span>
                              <span className="text-[10px] text-slate-500 font-mono">Rs. {item.price} • {item.weight || item.unit}</span>
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={() => handleRemoveProductFromCombine(item.id)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer"
                            title="Remove from group"
                          >
                            <X className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>

              {/* SECTION 2: Auto-Suggestions */}
              {(() => {
                const suggested = getSuggestedSiblings(combineModalProduct, selectedProductIdsToCombine);
                if (suggested.length === 0) return null;

                return (
                  <div className="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-2">
                        <Sparkles className="w-4 h-4 text-amber-600" />
                        <div>
                          <h5 className="text-xs font-black text-amber-900 uppercase tracking-wider">
                            ⚡ Auto-Suggested Matching Flavours & Sizes ({suggested.length})
                          </h5>
                          <span className="text-[10px] text-amber-700">
                            Matching brand keyword: <strong className="underline font-bold">{extractBrandKeywords(combineModalProduct.name)}</strong>
                          </span>
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={() => handleAddAllSuggested(suggested)}
                        className="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-black text-[10px] rounded-xl shadow-sm transition-all flex items-center gap-1 cursor-pointer"
                      >
                        <Sparkles className="w-3 h-3" /> Combine All Suggested (سب فلیورز ایک کلک میں جوڑیں)
                      </button>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                      {suggested.map(item => (
                        <div 
                          key={item.id}
                          className="bg-white border border-amber-200/80 rounded-xl p-2 flex items-center justify-between hover:border-amber-400 transition-all shadow-xs"
                        >
                          <div className="flex items-center gap-2 overflow-hidden">
                            <img
                              src={getProductImageUrl(item.image)}
                              alt={item.name}
                              className="w-8 h-8 object-contain rounded-lg border border-slate-100 bg-slate-50 p-0.5 flex-shrink-0"
                            />
                            <div className="truncate">
                              <span className="text-[11px] font-bold text-slate-800 block truncate" title={item.name}>{item.name}</span>
                              <span className="text-[10px] text-emerald-600 font-mono font-bold">Rs. {item.price} • {item.weight || item.unit}</span>
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={() => handleToggleProductInCombine(item.id)}
                            className="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-extrabold transition-all flex-shrink-0 cursor-pointer"
                          >
                            + Add
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })()}

              {/* SECTION 3: Search & Manual Selection */}
              <div className="space-y-3 pt-2 border-t border-slate-100">
                <div className="flex items-center justify-between">
                  <h5 className="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <Search className="w-3.5 h-3.5 text-slate-400" /> Search & Add Other Products
                  </h5>
                  <span className="text-[10px] text-slate-400 font-medium">Tick checkboxes to link</span>
                </div>

                <div className="relative">
                  <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    value={searchCombineQuery}
                    onChange={(e) => setSearchCombineQuery(e.target.value)}
                    placeholder="Search product title or barcode to combine..."
                    className="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white font-medium"
                  />
                </div>

                <div className="border border-slate-200 rounded-2xl overflow-hidden max-h-52 overflow-y-auto divide-y divide-slate-100 bg-white">
                  {products
                    .filter(p => {
                      if (p.id === combineModalProduct.id) return false;
                      if (!searchCombineQuery.trim()) {
                        return p.category === combineModalProduct.category || selectedProductIdsToCombine.includes(p.id);
                      }
                      return p.name.toLowerCase().includes(searchCombineQuery.toLowerCase()) ||
                             p.barcode.toLowerCase().includes(searchCombineQuery.toLowerCase());
                    })
                    .slice(0, 50)
                    .map(item => {
                      const isChecked = selectedProductIdsToCombine.includes(item.id);
                      return (
                        <label 
                          key={item.id} 
                          className={`p-2.5 flex items-center justify-between gap-3 hover:bg-slate-50 cursor-pointer transition-colors ${
                            isChecked ? 'bg-emerald-50/40' : ''
                          }`}
                        >
                          <div className="flex items-center gap-3 overflow-hidden">
                            <input
                              type="checkbox"
                              checked={isChecked}
                              onChange={() => handleToggleProductInCombine(item.id)}
                              className="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500"
                            />
                            <img
                              src={getProductImageUrl(item.image)}
                              alt={item.name}
                              className="w-8 h-8 object-contain rounded-lg border border-slate-100 bg-slate-50 p-0.5 flex-shrink-0"
                            />
                            <div className="truncate">
                              <span className="text-[11px] font-bold text-slate-800 block truncate">{item.name}</span>
                              <span className="text-[10px] text-slate-400 font-mono">Barcode: {item.barcode} • Rs. {item.price} • {item.weight || item.unit}</span>
                            </div>
                          </div>
                          {isChecked ? (
                            <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1 flex-shrink-0">
                              <Check className="w-3 h-3" /> Linked
                            </span>
                          ) : (
                            <span className="text-[10px] font-semibold text-slate-400 flex-shrink-0">
                              {getCategoryName(item.category)}
                            </span>
                          )}
                        </label>
                      );
                    })}
                </div>
              </div>

            </div>

            {/* Modal Footer */}
            <div className="p-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
              <span className="text-[11px] text-slate-500 font-medium text-left">
                Auto-Merge Rule: Save karte hi ye tamam items ek hi combined group ban jayenge.
              </span>
              <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button
                  type="button"
                  onClick={() => !savingGroup && setCombineModalProduct(null)}
                  disabled={savingGroup}
                  className="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 bg-white border border-slate-300 rounded-xl transition-all"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleSaveCombineGroup}
                  disabled={savingGroup}
                  className="px-5 py-2 text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md disabled:bg-slate-300 flex items-center gap-1.5 cursor-pointer"
                >
                  {savingGroup ? (
                    <span>Saving Combined Group...</span>
                  ) : (
                    <>
                      <Link2 className="w-3.5 h-3.5" />
                      <span>Save Combined Group (محفوظ کریں)</span>
                    </>
                  )}
                </button>
              </div>
            </div>

          </div>
        </div>
      )}

    </div>
  );
};
