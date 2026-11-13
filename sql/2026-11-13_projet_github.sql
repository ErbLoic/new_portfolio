-- Lien GitHub facultatif sur les projets. Ajout uniquement.
ALTER TABLE projects ADD COLUMN IF NOT EXISTS github_url varchar(500) NULL;
