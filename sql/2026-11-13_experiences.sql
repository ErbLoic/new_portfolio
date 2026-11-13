-- Stages -> Expériences : type (stage / alternance / job) et expérience en cours.
-- Ajout uniquement (aucune donnée supprimée). Les lignes existantes deviennent des « stage » terminés.
ALTER TABLE stages ADD COLUMN IF NOT EXISTS type varchar(20) NOT NULL DEFAULT 'stage';
ALTER TABLE stages ADD COLUMN IF NOT EXISTS is_current boolean NOT NULL DEFAULT false;
ALTER TABLE stages ALTER COLUMN end_date DROP NOT NULL;
