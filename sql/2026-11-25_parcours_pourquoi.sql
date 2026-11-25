-- Frise du parcours : « pourquoi ce choix ? » par étape, point de départ et suite. Ajout uniquement.
ALTER TABLE stages ADD COLUMN IF NOT EXISTS reason text NULL;
ALTER TABLE formations ADD COLUMN IF NOT EXISTS reason text NULL;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS timeline_start text NULL;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS timeline_next text NULL;
