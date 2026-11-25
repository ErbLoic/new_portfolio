-- Expérience (stage / alternance) réalisée dans le cadre d'une formation. Ajout uniquement.
ALTER TABLE stages ADD COLUMN IF NOT EXISTS formation_id bigint NULL REFERENCES formations(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_stages_formation ON stages(formation_id);
