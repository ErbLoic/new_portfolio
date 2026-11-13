-- Lien optionnel projet -> école (formation). Ajout uniquement, aucune donnée modifiée.
ALTER TABLE projects ADD COLUMN IF NOT EXISTS formation_id bigint NULL REFERENCES formations(id) ON DELETE SET NULL;
