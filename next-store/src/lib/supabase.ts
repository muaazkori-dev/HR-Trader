// Supabase client initialization - Safe for static build
import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://xarwwlbbaevclyljkvzt.supabase.co';
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || 'sb_publishable_v3-WUAhugqCtckYHXxcQNg_H-mBrrC4';

export const supabase = createClient(supabaseUrl, supabaseAnonKey);
