-- Description longue des écoles (affichée sur les pages des projets liés). Ajout uniquement.
ALTER TABLE formations ADD COLUMN IF NOT EXISTS long_description text NULL;
