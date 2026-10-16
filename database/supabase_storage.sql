-- Optional: create the PRIVATE bucket for medical reports from the SQL editor.
-- (The app also creates it automatically at start-up when SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY are set.)
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('medical-reports', 'medical-reports', false, 2097152, ARRAY['application/pdf','image/jpeg','image/png'])
ON CONFLICT (id) DO NOTHING;
