import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

export async function POST(request: Request) {
  try {
    const userAgent = request.headers.get('user-agent') || 'Unknown';
    const language = request.headers.get('accept-language') || 'Unknown';
    
    // Resolve IP address from headers
    let ip = request.headers.get('x-forwarded-for') || request.headers.get('x-real-ip') || '127.0.0.1';
    if (ip.includes(',')) {
      ip = ip.split(',')[0].trim();
    }

    // Insert log to database
    const { error } = await supabase
      .from('visitor_logs')
      .insert({
        user_agent: userAgent,
        language: language,
        ip: ip
      });

    if (error) {
      console.error('Error inserting visitor log:', error);
      return NextResponse.json({ success: false, error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true });
  } catch (err: any) {
    console.error('Visitor logging handler crashed:', err);
    return NextResponse.json({ success: false, error: err.message }, { status: 500 });
  }
}
