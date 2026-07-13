'use client';

import { useEffect } from 'react';
import { supabase } from '@/lib/supabase';

export const ThemeManager: React.FC = () => {
  useEffect(() => {
    const applyTheme = async () => {
      try {
        const { data, error } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'active_theme')
          .single();
          
        if (!error && data?.val_value) {
          const newThemeClass = `theme-${data.val_value}`;
          
          // Remove existing theme classes from body
          const classesToRemove: string[] = [];
          document.body.classList.forEach((cls) => {
            if (cls.startsWith('theme-')) {
              classesToRemove.push(cls);
            }
          });
          classesToRemove.forEach((cls) => document.body.classList.remove(cls));
          
          // Add the new theme class
          document.body.classList.add(newThemeClass);
        }
      } catch (err) {
        console.error('Failed to resolve theme dynamically:', err);
      }
    };

    applyTheme();
    
    // Subscribe to settings table changes for real-time theme updates in the browser!
    const channel = supabase
      .channel('theme-realtime-channel')
      .on(
        'postgres_changes',
        { event: 'UPDATE', schema: 'public', table: 'settings', filter: 'key_name=eq.active_theme' },
        (payload: any) => {
          const nextVal = payload.new?.val_value;
          if (nextVal) {
            const nextThemeClass = `theme-${nextVal}`;
            const removeList: string[] = [];
            document.body.classList.forEach((cls) => {
              if (cls.startsWith('theme-')) {
                removeList.push(cls);
              }
            });
            removeList.forEach((cls) => document.body.classList.remove(cls));
            document.body.classList.add(nextThemeClass);
          }
        }
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
  }, []);

  return null;
};
