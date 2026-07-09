// Supabase client initialization - Safe for static build
import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://placeholder-xarwwlbbaevclyljkvzt.supabase.co';
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || 'placeholder-anon-key-WUAhugqCtckYHXxcQNg';

export const supabase = createClient(supabaseUrl, supabaseAnonKey);
