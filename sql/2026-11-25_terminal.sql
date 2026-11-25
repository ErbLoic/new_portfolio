-- Contenus du terminal caché (easter egg « sudo »), modifiables depuis l'admin. Ajout uniquement.
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS terminal_motivation varchar(255) NULL;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS terminal_cowsay varchar(255) NULL;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS terminal_fortunes text NULL;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS terminal_commands text NULL;
