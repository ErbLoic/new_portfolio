-- Mois + année des projets, réalisations et projets perso (tri du plus récent au plus ancien).
-- Ajout uniquement : les éléments existants gardent leur année en attendant que tu choisisses le mois.
ALTER TABLE projects ADD COLUMN IF NOT EXISTS project_date date NULL;
ALTER TABLE realisations ADD COLUMN IF NOT EXISTS project_date date NULL;
ALTER TABLE personal_projects ADD COLUMN IF NOT EXISTS project_date date NULL;
