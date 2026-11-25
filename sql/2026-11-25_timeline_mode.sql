-- Affichage du parcours : 'split' (formations et expériences séparées) ou 'merged' (une seule frise). Ajout uniquement.
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS timeline_mode varchar(10) NOT NULL DEFAULT 'split';
