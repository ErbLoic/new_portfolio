-- Titre affiché sous le nom (portfolio personnel). Ajout uniquement.
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS job_title varchar(255) NULL;
