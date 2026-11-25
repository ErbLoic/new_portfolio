-- Missions en alternance, certifications, compétences techniques (niveau / icône).
-- Ajout uniquement, idempotent. RLS activée sans policy (cohérent avec 2026-11-25_rls_purge.sql).
CREATE TABLE IF NOT EXISTS missions (
    id bigserial PRIMARY KEY,
    stage_id bigint NOT NULL REFERENCES stages(id) ON DELETE CASCADE,
    title varchar(255) NOT NULL,
    context text NULL,
    actions text NULL,
    results text NULL,
    tags varchar(255) NULL,
    "order" int NOT NULL DEFAULT 0,
    is_published boolean NOT NULL DEFAULT true,
    created_at timestamp NULL,
    updated_at timestamp NULL
);
CREATE INDEX IF NOT EXISTS idx_missions_stage ON missions(stage_id);

CREATE TABLE IF NOT EXISTS certifications (
    id bigserial PRIMARY KEY,
    name varchar(255) NOT NULL,
    issuer varchar(255) NOT NULL,
    issued_at date NULL,
    expires_at date NULL,
    credential_url varchar(255) NULL,
    badge_url varchar(255) NULL,
    "order" int NOT NULL DEFAULT 0,
    created_at timestamp NULL,
    updated_at timestamp NULL
);

ALTER TABLE competences ADD COLUMN IF NOT EXISTS level smallint NOT NULL DEFAULT 3;
ALTER TABLE competences ADD COLUMN IF NOT EXISTS icon varchar(60) NULL;

ALTER TABLE missions ENABLE ROW LEVEL SECURITY;
ALTER TABLE certifications ENABLE ROW LEVEL SECURITY;
