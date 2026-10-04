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
    const action = searchParams.get('action');
    const orderId = searchParams.get('id') || searchParams.get('order_id');
    const phone = searchParams.get('phone');

    // 1. Order Detail / Live Tracking
    if (action === 'track' || orderId) {
      if (!orderId) {
        return NextResponse.json({ success: false, message: 'Order ID is required' }, { status: 400, headers: corsHeaders });
      }

      const { data: order, error: orderErr } = await supabase
        .from('orders')
        .select('*')
        .eq('id', orderId)
        .maybeSingle();

      if (orderErr || !order) {
        return NextResponse.json({ success: false, message: 'Order not found' }, { status: 404, headers: corsHeaders });
      }

      // Fetch items for this order
      const { data: items } = await supabase
        .from('order_items')
        .select('*')
        .eq('order_id', orderId);

      return NextResponse.json({
        success: true,
        data: {
          ...order,
          items: items || [],
        },
      }, { headers: corsHeaders });
    }

    // 2. Orders List by Phone or All
    let query = supabase.from('orders').select('*').order('created_at', { ascending: false });

    if (phone) {
      query = query.eq('customer_phone', phone.trim());
    }

    const { data: orders, error } = await query.limit(50);

    if (error) {
      return NextResponse.json({ success: false, message: error.message }, { status: 500, headers: corsHeaders });
    }

    return NextResponse.json({
      success: true,
      data: orders || [],
    }, { headers: corsHeaders });

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
    const {
      customer_name,
      customer_phone,
      customer_address,
      latitude,
      longitude,
      delivery_notes,
      payment_method = 'COD',
      items = [],
      coupon_code,
      discount_amount = 0,
    } = body;

    if (!customer_name || !customer_phone || !customer_address) {
      return NextResponse.json(
        { success: false, message: 'Name, phone, and delivery address are required' },
        { status: 400, headers: corsHeaders }
      );
    }

    if (!Array.isArray(items) || items.length === 0) {
      return NextResponse.json(
        { success: false, message: 'Cart items cannot be empty' },
        { status: 400, headers: corsHeaders }
      );
    }

    // 1. Fetch missing product details (prices & names) from DB if not provided
    const productIds = items
      .map((it: any) => it.product_id || it.id)
      .filter(Boolean);

    const dbProductMap = new Map<number, any>();
    if (productIds.length > 0) {
      const { data: dbProducts } = await supabase
        .from('products')
        .select('id, name, price')
        .in('id', productIds);

      if (dbProducts) {
        dbProducts.forEach((p: any) => dbProductMap.set(p.id, p));
      }
    }

    // 2. Calculate subtotal and build enriched order items
    let subtotal = 0;
    const orderItemsToInsert: any[] = [];

    for (const it of items) {
      const pId = it.product_id || it.id;
      const dbProd = pId ? dbProductMap.get(pId) : null;

      const p = parseFloat(it.price) || (dbProd ? parseFloat(dbProd.price) : 0);
      const q = parseInt(it.quantity) || 1;
      const name = it.product_name || it.name || dbProd?.name || 'Product';

      subtotal += p * q;
      orderItemsToInsert.push({
        product_id: pId,
        price: p,
        quantity: q,
        product_name: name,
      });
    }

    // Shipping fee calculation (Free above 2500, else default 180)
    const shippingFee = subtotal >= 2500 ? 0 : 180;
    const finalDiscount = parseFloat(discount_amount) || 0;
    const totalAmount = Math.max(0, subtotal - finalDiscount + shippingFee);

    // Format address with GPS coordinates if provided
    let fullAddress = customer_address.trim();
    if (latitude && longitude) {
      const mapUrl = `https://www.google.com/maps?q=${latitude},${longitude}`;
      fullAddress += `\n📍 Live GPS: ${mapUrl} (Lat: ${latitude}, Lng: ${longitude})`;
    }

    // Insert Order
    const { data: order, error: orderErr } = await supabase
      .from('orders')
      .insert([
        {
          customer_name: customer_name.trim(),
          customer_phone: customer_phone.trim(),
          customer_address: fullAddress,
          total_amount: totalAmount,
          payment_method: payment_method,
          status: 'pending',
          notes: delivery_notes?.trim() || null,
          coupon_code: coupon_code || null,
          discount_amount: finalDiscount,
        },
      ])
      .select()
      .single();

    if (orderErr || !order) {
      return NextResponse.json(
        { success: false, message: 'Failed to record order: ' + orderErr?.message },
        { status: 500, headers: corsHeaders }
      );
    }

    // Insert Order Items
    const orderItems = orderItemsToInsert.map((it) => ({
      order_id: order.id,
      ...it,
    }));

    const { error: itemsErr } = await supabase.from('order_items').insert(orderItems);

    if (itemsErr) {
      console.error('Failed to insert order items:', itemsErr);
    }

    const formattedOrderRef = `#HRT-${String(order.id).padStart(5, '0')}`;

    return NextResponse.json({
      success: true,
      message: 'Order placed successfully!',
      data: {
        order_id: order.id,
        order_ref: formattedOrderRef,
        total_amount: totalAmount,
        status: 'pending',
      },
    }, { status: 201, headers: corsHeaders });

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Server error processing order' },
      { status: 500, headers: corsHeaders }
    );
  }
}
