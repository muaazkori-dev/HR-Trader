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

export async function GET() {
  try {
    const { data: settings, error } = await supabase
      .from('settings')
      .select('key_name, val_value');

    if (error) throw error;

    const map: Record<string, any> = {};
    (settings || []).forEach((s) => {
      map[s.key_name] = s.val_value;
    });

    const shippingFee = parseFloat(map['shipping_fee'] || '100');
    const freeThreshold = parseFloat(map['free_shipping_threshold'] || '2500');
    const minOrder = parseFloat(map['min_order_value'] || '0');
    const shopStatus = map['shop_status'] || 'open';

    return NextResponse.json({
      success: true,
      data: {
        ...map,
        shipping_fee: isNaN(shippingFee) ? 100 : shippingFee,
        free_shipping_threshold: isNaN(freeThreshold) ? 2500 : freeThreshold,
        min_order_value: isNaN(minOrder) ? 0 : minOrder,
        shop_status: shopStatus,
        store_phone: map['store_phone'] || '+92 303 3943814',
        store_whatsapp: map['whatsapp_number'] || map['store_whatsapp'] || '923033943814',
        default_rider_phone: map['default_rider_phone'] || '03033943814',
        default_rider_name: map['default_rider_name'] || 'Store Rider',
        store_address: map['branch_1_address'] || map['store_address'] || 'Toor Colony, Tando Adam',
      },
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
    const { key_name, val_value } = body;

    if (!key_name) {
      return NextResponse.json(
        { success: false, message: 'key_name is required' },
        { status: 400, headers: corsHeaders }
      );
    }

    const { data, error } = await supabase
      .from('settings')
      .upsert({ key_name, val_value: val_value?.toString() ?? '' }, { onConflict: 'key_name' })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json({
      success: true,
      message: `Setting '${key_name}' updated successfully`,
      data,
    }, { headers: corsHeaders });

  } catch (err: any) {
    return NextResponse.json(
      { success: false, message: err?.message || 'Server error' },
      { status: 500, headers: corsHeaders }
    );
  }
}
