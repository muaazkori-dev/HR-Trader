import { NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';
import webpush from 'web-push';

export async function POST(request: Request) {
  try {
    const { phone, title, body, url } = await request.json();

    if (!phone || !title || !body) {
      return NextResponse.json({ success: false, error: 'Phone, title, and body are required.' }, { status: 400 });
    }

    // 1. Fetch VAPID keys from Settings table
    let publicKey = '';
    let privateKey = '';

    const { data: dbKeys, error: keyError } = await supabase
      .from('settings')
      .select('key_name, val_value')
      .in('key_name', ['vapid_public_key', 'vapid_private_key']);

    if (!keyError && dbKeys && dbKeys.length === 2) {
      const pubItem = dbKeys.find(k => k.key_name === 'vapid_public_key');
      const privItem = dbKeys.find(k => k.key_name === 'vapid_private_key');
      if (pubItem && privItem) {
        publicKey = pubItem.val_value || '';
        privateKey = privItem.val_value || '';
      }
    }

    // Generate keys on the fly if missing from settings table
    if (!publicKey || !privateKey) {
      const vapidKeys = webpush.generateVAPIDKeys();
      publicKey = vapidKeys.publicKey;
      privateKey = vapidKeys.privateKey;

      const { error: upsertError } = await supabase
        .from('settings')
        .upsert([
          { key_name: 'vapid_public_key', val_value: publicKey },
          { key_name: 'vapid_private_key', val_value: privateKey }
        ], { onConflict: 'key_name' });

      if (upsertError) {
        console.error('Error saving generated VAPID keys to DB:', upsertError);
        return NextResponse.json({ success: false, error: 'Failed to initialize keys' }, { status: 500 });
      }
    }

    // Set VAPID details
    webpush.setVapidDetails(
      'mailto:support@thehrtraders.com',
      publicKey,
      privateKey
    );

    // 2. Fetch push subscriptions matching customer phone number
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const { data: subscriptions, error: subError } = await supabase
      .from('push_subscriptions')
      .select('*')
      .eq('customer_phone', cleanPhone);

    if (subError) {
      console.error('Error loading subscriptions:', subError);
      return NextResponse.json({ success: false, error: subError.message }, { status: 500 });
    }

    if (!subscriptions || subscriptions.length === 0) {
      return NextResponse.json({ success: true, message: 'No registered push subscriptions found for this phone.' });
    }

    // 3. Dispatch web push alerts
    const payload = JSON.stringify({
      title,
      body,
      url: url || '/'
    });

    const results = await Promise.all(
      subscriptions.map(async (sub) => {
        try {
          // Parse subscription column (JSONB)
          const parsedSub = typeof sub.subscription === 'string' 
            ? JSON.parse(sub.subscription) 
            : sub.subscription;
            
          await webpush.sendNotification(parsedSub, payload);
          return { id: sub.id, success: true };
        } catch (err: any) {
          console.error(`Failed sending to subscription ${sub.id}:`, err);
          
          // Clean up expired or obsolete subscription endpoints
          if (err.statusCode === 410 || err.statusCode === 404) {
            await supabase
              .from('push_subscriptions')
              .delete()
              .eq('id', sub.id);
            return { id: sub.id, cleaned: true };
          }
          return { id: sub.id, error: err.message };
        }
      })
    );

    return NextResponse.json({ success: true, results });
  } catch (err: any) {
    console.error('Push notification endpoint crashed:', err);
    return NextResponse.json({ success: false, error: err.message }, { status: 500 });
  }
}
