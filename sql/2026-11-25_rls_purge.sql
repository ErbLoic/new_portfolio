-- ============================================================================
--  Sécurité Supabase + ménage des tables héritées de Laravel
--  Idempotent : peut être rejoué sans erreur.
--  À exécuter avec le rôle propriétaire des tables (éditeur SQL Supabase).
--  ⚠ Faire une sauvegarde avant (Supabase : Database → Backups).
-- ============================================================================
BEGIN;

-- 1. Purge des tables Laravel non utilisées par l'application Symfony
DROP TABLE IF EXISTS
    public.cache,
    public.cache_locks,
    public.custom_pages,
    public.failed_jobs,
    public.job_batches,
    public.jobs,
    public.migrations,
    public.password_reset_tokens,
    public.sessions
CASCADE;

-- 2. RLS activée sur toutes les tables du schéma public, sans aucune policy :
--    l'API Supabase (rôles anon / authenticated) ne peut plus rien lire ni écrire.
--    L'application Symfony se connecte avec le rôle propriétaire : elle n'est pas
--    concernée par la RLS (pas de FORCE ROW LEVEL SECURITY).
DO $$
DECLARE t record;
BEGIN
    FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', t.tablename);
    END LOOP;
END $$;

-- 3. Défense en profondeur : retrait des droits des rôles de l'API Supabase
--    (ignoré si ces rôles n'existent pas, ex. base locale)
DO $$
DECLARE r text;
BEGIN
    FOREACH r IN ARRAY ARRAY['anon', 'authenticated'] LOOP
        IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = r) THEN
            EXECUTE format('REVOKE ALL ON ALL TABLES IN SCHEMA public FROM %I', r);
            EXECUTE format('REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM %I', r);
            EXECUTE format('REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM %I', r);
            EXECUTE format('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM %I', r);
            EXECUTE format('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM %I', r);
        END IF;
    END LOOP;
END $$;

COMMIT;

-- Vérification : toutes les lignes doivent avoir rls = true
SELECT tablename, rowsecurity AS rls FROM pg_tables WHERE schemaname = 'public' ORDER BY 1;

-- 4. Formulaire de contact supprimé du site : colonne devenue inutile
ALTER TABLE public.portfolios DROP COLUMN IF EXISTS show_contact_form;
