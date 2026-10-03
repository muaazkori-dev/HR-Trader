import { NextRequest, NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

// Helper for CORS headers
const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization',
};

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders });
}

const DEFAULT_CATEGORIES = [
  { id: 'anaj', slug: 'anaj', name: 'BAKING AND COOKING', urdu: 'اناج', image: 'https://thehrtraders.com/assets/images/categories/anaj.png', product_count: 0 },
  { id: 'grocery', slug: 'grocery', name: 'GROCERY & OILS', urdu: 'گروسری', image: 'https://thehrtraders.com/assets/images/categories/grocery.png', product_count: 0 },
  { id: 'ice_cream', slug: 'ice_cream', name: 'ICE CREAM', urdu: 'آئس کریم', image: 'https://thehrtraders.com/assets/images/categories/ice_cream.png', product_count: 0 },
  { id: 'beverages', slug: 'beverages', name: 'BEVERAGES', urdu: 'مشروبات', image: 'https://thehrtraders.com/assets/images/categories/cold_drinks.png', product_count: 0 },
  { id: 'milk', slug: 'milk', name: 'HERBAL & NUTRITION', urdu: 'دودھ', image: 'https://thehrtraders.com/assets/images/categories/milk.png', product_count: 0 },
  { id: 'cosmetics', slug: 'cosmetics', name: 'COSMETICS', urdu: 'کاسمیٹکس', image: 'https://thehrtraders.com/assets/images/categories/cosmetics.png', product_count: 0 },
  { id: 'confectionary', slug: 'confectionary', name: 'SNACKS & CHIPS', urdu: 'سنیکس', image: 'https://thehrtraders.com/assets/images/categories/snacks.png', product_count: 0 },
  { id: 'bakery', slug: 'bakery', name: 'DAIRY & BREAKFAST', urdu: 'بیکری', image: 'https://thehrtraders.com/assets/images/categories/bakery.png', product_count: 0 },
  { id: 'sauce', slug: 'sauce', name: 'PASTA & SAUCES', urdu: 'سوس', image: 'https://thehrtraders.com/assets/images/categories/sauce.png', product_count: 0 },
];

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const action = searchParams.get('action') || 'list';
    const id = searchParams.get('id');

    // 1. Single Product Detail
    if (action === 'detail' || (id && !action)) {
      const prodId = id || searchParams.get('product_id');
      if (!prodId) {
        return NextResponse.json({ success: false, message: 'Missing product ID' }, { status: 400, headers: corsHeaders });
      }

      const { data: product, error } = await supabase
        .from('products')
        .select('*')
        .eq('id', prodId)
        .maybeSingle();

      if (error || !product) {
        return NextResponse.json({ success: false, message: 'Product not found' }, { status: 404, headers: corsHeaders });
      }

      return NextResponse.json({ success: true, data: product }, { headers: corsHeaders });
    }

    // 2. Categories
    if (action === 'categories') {
      const { data: catSetting } = await supabase
        .from('settings')
        .select('val_value')
        .eq('key_name', 'store_categories')
        .maybeSingle();

      let categories = DEFAULT_CATEGORIES;

      if (catSetting?.val_value) {
        try {
          const parsed = JSON.parse(catSetting.val_value);
          if (Array.isArray(parsed)) {
            categories = parsed.map((cat: any) => ({
              id: cat.id || cat.key || '',
              slug: cat.id || cat.key || '',
              name: cat.name || '',
              urdu: cat.urdu || '',
              image: cat.image || '',
              product_count: 0,
            }));
          } else if (typeof parsed === 'object') {
            categories = Object.entries(parsed).map(([key, val]: [string, any]) => ({
              id: key,
              slug: key,
              name: val.name || '',
              urdu: val.urdu || '',
              image: val.image || '',
              product_count: 0,
            }));
          }
        } catch (e) {
          console.error('Error parsing categories:', e);
        }
      }

      return NextResponse.json({ success: true, data: categories }, { headers: corsHeaders });
    }

    // 3. Featured Products & Banners
    if (action === 'featured') {
      const [prodRes, bannerRes] = await Promise.all([
        supabase.from('products').select('*').order('id', { ascending: false }).limit(10),
        supabase.from('settings').select('val_value').eq('key_name', 'store_hero_banners').maybeSingle(),
      ]);

      let banners: any[] = [];
      if (bannerRes.data?.val_value) {
        try {
          banners = JSON.parse(bannerRes.data.val_value);
        } catch (e) {
          console.error('Error parsing hero banners:', e);
        }
      }

      return NextResponse.json({
        success: true,
        data: {
          featured_products: prodRes.data || [],
          banners: banners,
        },
      }, { headers: corsHeaders });
    }

    // 4. Banners only
    if (action === 'banners') {
      const { data: bannerSetting } = await supabase
        .from('settings')
        .select('val_value')
        .eq('key_name', 'store_hero_banners')
        .maybeSingle();

      let banners: any[] = [];
      if (bannerSetting?.val_value) {
        try {
          banners = JSON.parse(bannerSetting.val_value);
        } catch (e) {
          console.error('Error parsing hero banners:', e);
        }
      }

      return NextResponse.json({ success: true, data: banners }, { headers: corsHeaders });
    }

    // 5. Product List / Search / Filters / Pagination
    const category = searchParams.get('category');
    const search = searchParams.get('search');
    const sort = searchParams.get('sort');
    const page = Math.max(1, parseInt(searchParams.get('page') || '1'));
    const limit = Math.min(50, Math.max(1, parseInt(searchParams.get('limit') || '20')));
    const from = (page - 1) * limit;
    const to = from + limit - 1;

    let query = supabase.from('products').select('*', { count: 'exact' });

    if (category && category !== 'all') {
      query = query.eq('category', category);
    }

    if (search && search.trim()) {
      query = query.ilike('name', `%${search.trim()}%`);
    }

    if (sort === 'price_asc') {
      query = query.order('price', { ascending: true });
    } else if (sort === 'price_desc') {
      query = query.order('price', { ascending: false });
    } else if (sort === 'name') {
      query = query.order('name', { ascending: true });
    } else {
      query = query.order('id', { ascending: false });
    }

    const { data: products, count, error } = await query.range(from, to);

    if (error) {
      return NextResponse.json({ success: false, message: error.message }, { status: 500, headers: corsHeaders });
    }

    const total = count || 0;
    const totalPages = Math.ceil(total / limit);

    return NextResponse.json({
      success: true,
      data: {
        products: products || [],
        pagination: {
          total,
          page,
          limit,
          total_pages: totalPages,
        },
      },
    }, { headers: corsHeaders });

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Internal server error' },
      { status: 500, headers: corsHeaders }
    );
  }
}
