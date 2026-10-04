import { NextRequest, NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization',
};

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders });
}

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const action = searchParams.get('action') || 'dashboard';

    // 1. Dashboard Metrics
    if (action === 'dashboard') {
      const todayStart = new Date();
      todayStart.setHours(0, 0, 0, 0);

      // Today's orders
      const { data: todayOrders } = await supabase
        .from('orders')
        .select('total_amount, status')
        .gte('created_at', todayStart.toISOString());

      const todayGross = (todayOrders || [])
        .filter((o) => o.status !== 'cancelled')
        .reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);

      // Pending orders count
      const { count: pendingCount } = await supabase
        .from('orders')
        .select('*', { count: 'exact', head: true })
        .in('status', ['pending', 'packaging', 'out_for_delivery']);

      // Delivered today count
      const deliveredTodayCount = (todayOrders || []).filter((o) => o.status === 'delivered').length;

      // Low stock count (< 5)
      const { count: lowStockCount } = await supabase
        .from('products')
        .select('*', { count: 'exact', head: true })
        .lte('stock_quantity', 5);

      // Total products count
      const { count: totalProductsCount } = await supabase
        .from('products')
        .select('*', { count: 'exact', head: true });

      // Recent 10 orders
      const { data: recentOrders } = await supabase
        .from('orders')
        .select('*, order_items(*)')
        .order('id', { ascending: false })
        .limit(10);

      return NextResponse.json({
        success: true,
        data: {
          today_revenue: todayGross,
          today_orders_count: todayOrders?.length || 0,
          delivered_today_count: deliveredTodayCount,
          pending_orders_count: pendingCount || 0,
          low_stock_count: lowStockCount || 0,
          total_products_count: totalProductsCount || 0,
          recent_orders: recentOrders || [],
        },
      }, { headers: corsHeaders });
    }

    // 2. Settings map
    if (action === 'settings') {
      const { data: settings, error } = await supabase
        .from('settings')
        .select('*');

      if (error) throw error;

      const map: Record<string, string> = {};
      (settings || []).forEach((s) => {
        map[s.key_name] = s.val_value;
      });

      return NextResponse.json({
        success: true,
        data: map,
      }, { headers: corsHeaders });
    }

    // 3. Customer Demands
    if (action === 'demands') {
      const { data: demands, error } = await supabase
        .from('demands')
        .select('*')
        .order('created_at', { ascending: false })
        .limit(50);

      if (error) throw error;

      return NextResponse.json({
        success: true,
        data: demands || [],
      }, { headers: corsHeaders });
    }

    // 4. Staff & Riders list
    if (action === 'staff') {
      const { data: staff, error } = await supabase
        .from('profiles')
        .select('*')
        .in('role', ['owner', 'manager', 'rider', 'cashier'])
        .order('name', { ascending: true });

      if (error) throw error;

      return NextResponse.json({
        success: true,
        data: staff || [],
      }, { headers: corsHeaders });
    }

    return NextResponse.json({ success: false, message: 'Invalid action' }, { status: 400, headers: corsHeaders });

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Server error' },
      { status: 500, headers: corsHeaders }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { action } = body;

    // 1. Quick Stock Toggle (In-stock / Out-of-stock)
    if (action === 'toggle_stock') {
      const { product_id, in_stock } = body;
      const newStock = in_stock ? 15 : 0;

      const { data, error } = await supabase
        .from('products')
        .update({ stock_quantity: newStock })
        .eq('id', product_id)
        .select()
        .single();

      if (error) throw error;

      return NextResponse.json({
        success: true,
        message: in_stock ? 'Product marked in-stock' : 'Product marked out-of-stock',
        data,
      }, { headers: corsHeaders });
    }

    // 2. Quick Price & Stock Update
    if (action === 'update_product_quick') {
      const { product_id, price, stock_quantity, purchase_price } = body;
      const updateData: Record<string, any> = {};

      if (price !== undefined) updateData.price = parseFloat(price);
      if (stock_quantity !== undefined) updateData.stock_quantity = parseInt(stock_quantity);
      if (purchase_price !== undefined) updateData.purchase_price = parseFloat(purchase_price);

      const { data, error } = await supabase
        .from('products')
        .update(updateData)
        .eq('id', product_id)
        .select()
        .single();

      if (error) throw error;

      return NextResponse.json({
        success: true,
        message: 'Product updated successfully',
        data,
      }, { headers: corsHeaders });
    }

    // 3. Add New Product
    if (action === 'add_product') {
      const { name, category, price, purchase_price, stock_quantity, unit, barcode, image } = body;

      if (!name || !price) {
        return NextResponse.json(
          { success: false, message: 'Product name and price are required' },
          { status: 400, headers: corsHeaders }
        );
      }

      const { data, error } = await supabase
        .from('products')
        .insert([
          {
            name: name.trim(),
            category: category || 'anaj',
            price: parseFloat(price) || 0,
            purchase_price: parseFloat(purchase_price) || 0,
            stock_quantity: parseInt(stock_quantity) || 10,
            unit: unit || 'pack',
            barcode: barcode || null,
            image: image || 'https://xarwwlbbaevclyljkvzt.supabase.co/storage/v1/object/public/product-images/default.png',
          },
        ])
        .select()
        .single();

      if (error) throw error;

      return NextResponse.json({
        success: true,
        message: 'New product added successfully',
        data,
      }, { headers: corsHeaders });
    }

    // 4. Update Setting Value
    if (action === 'update_setting') {
      const { key_name, val_value } = body;

      const { data, error } = await supabase
        .from('settings')
        .upsert({ key_name, val_value }, { onConflict: 'key_name' })
        .select()
        .single();

      if (error) throw error;

      return NextResponse.json({
        success: true,
        message: `Setting ${key_name} updated`,
        data,
      }, { headers: corsHeaders });
    }

    // 5. POS Counter Sale
    if (action === 'pos_sale') {
      const { customer_name = 'Walk-in Counter Customer', items = [] } = body;

      let subtotal = 0;
      for (const it of items) {
        subtotal += (parseFloat(it.price) || 0) * (parseInt(it.quantity) || 1);
      }

      // Create Order
      const { data: order, error: orderErr } = await supabase
        .from('orders')
        .insert([
          {
            customer_name,
            customer_phone: 'Walk-in',
            customer_address: 'Counter / In-Store Sale, Tando Adam',
            total_amount: subtotal,
            payment_method: 'CASH_COUNTER',
            status: 'delivered',
            notes: 'Walk-in POS Sale',
          },
        ])
        .select()
        .single();

      if (orderErr) throw orderErr;

      // Insert Items & Decrement Stock
      const orderItems = items.map((it: any) => ({
        order_id: order.id,
        product_id: it.product_id || it.id,
        price: parseFloat(it.price) || 0,
        quantity: parseInt(it.quantity) || 1,
        product_name: it.product_name || it.name || 'Product',
      }));

      await supabase.from('order_items').insert(orderItems);

      // Decrement stock in DB
      for (const it of items) {
        const pId = it.product_id || it.id;
        const qty = parseInt(it.quantity) || 1;
        const { data: prod } = await supabase.from('products').select('stock_quantity').eq('id', pId).single();
        if (prod) {
          const newQty = Math.max(0, (prod.stock_quantity || 0) - qty);
          await supabase.from('products').update({ stock_quantity: newQty }).eq('id', pId);
        }
      }

      return NextResponse.json({
        success: true,
        message: 'POS Counter Sale completed successfully',
        data: {
          order_id: order.id,
          total_amount: subtotal,
        },
      }, { headers: corsHeaders });
    }

    return NextResponse.json({ success: false, message: 'Invalid action' }, { status: 400, headers: corsHeaders });

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Server error' },
      { status: 500, headers: corsHeaders }
    );
  }
}
