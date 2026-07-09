import React from 'react';
import { supabase } from '@/lib/supabase';
import { SettingsContent } from '@/components/admin/SettingsContent';

export const revalidate = 0; // Fresh settings values always

export default async function AdminSettingsPage() {
  let settings: any[] = [];

  try {
    const { data, error } = await supabase
      .from('settings')
      .select('key_name, val_value');

    if (!error && data) {
      settings = data;
    }
  } catch (err) {
    console.error('Error fetching configuration settings:', err);
  }

  return (
    <div className="w-full flex-grow flex flex-col">
      <SettingsContent initialSettings={settings} />
    </div>
  );
}
