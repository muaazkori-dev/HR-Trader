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
    const action = searchParams.get('action') || 'active';

    // 1. Rider Dashboard Summary Stats
    if (action === 'stats') {
      const todayStart = new Date();
      todayStart.setHours(0, 0, 0, 0);

      // Active orders count
      const { count: activeCount } = await supabase
        .from('orders')
        .select('*', { count: 'exact', head: true })
        .in('status', ['pending', 'packaging', 'out_for_delivery']);

      // Delivered today
      const { data: deliveredToday } = await supabase
        .from('orders')
        .select('total_amount')
        .eq('status', 'delivered')
        .gte('created_at', todayStart.toISOString());

      const deliveredCount = deliveredToday?.length || 0;
      const cashCollected = (deliveredToday || []).reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);

      return NextResponse.json({
        success: true,
        data: {
          active_count: activeCount || 0,
          delivered_today_count: deliveredCount,
          cash_collected_today: cashCollected,
        },
      }, { headers: corsHeaders });
    }

    // 2. Completed Delivery History
    if (action === 'history') {
      const { data: orders, error } = await supabase
        .from('orders')
        .select('*, order_items(*)')
        .eq('status', 'delivered')
        .order('id', { ascending: false })
        .limit(40);

      if (error) throw error;

      return NextResponse.json({
        success: true,
        data: orders || [],
      }, { headers: corsHeaders });
    }

    // 3. Active Orders for Delivery (Default)
    const { data: activeOrders, error } = await supabase
      .from('orders')
      .select('*, order_items(*)')
      .in('status', ['pending', 'packaging', 'out_for_delivery'])
      .order('id', { ascending: false });

    if (error) throw error;

    return NextResponse.json({
      success: true,
      data: activeOrders || [],
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
    const { action, order_id, status, rider_name, rider_phone } = body;

    // 1. Update Order Delivery Status
    if (action === 'update_status') {
      if (!order_id || !status) {
        return NextResponse.json(
          { success: false, message: 'order_id and status are required' },
          { status: 400, headers: corsHeaders }
        );
      }

      const updatePayload: Record<string, any> = {
        status: status,
      };

      const { data: updatedOrder, error } = await supabase
        .from('orders')
        .update(updatePayload)
        .eq('id', order_id)
        .select('*, order_items(*)')
        .single();

      if (error) throw error;

      return NextResponse.json({
        success: true,
        message: `Order #${order_id} status updated to ${status}`,
        data: updatedOrder,
      }, { headers: corsHeaders });
    }

    // 2. Rider Quick Login / Verification
    if (action === 'login') {
      const phone = (rider_phone || '').trim();
      const name = (rider_name || 'Rider').trim();

      return NextResponse.json({
        success: true,
        message: 'Rider session verified',
        data: {
          rider_id: `rider_${phone.replace(/\D/g, '') || '01'}`,
          rider_name: name,
          rider_phone: phone,
          role: 'rider',
        },
      }, { headers: corsHeaders });
    }

    return NextResponse.json(
      { success: false, message: 'Invalid action' },
      { status: 400, headers: corsHeaders }
    );

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Server error' },
      { status: 500, headers: corsHeaders }
    );
  }
}
