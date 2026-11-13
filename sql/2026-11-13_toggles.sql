-- Interrupteurs « formulaire de contact » et « CV » (activés par défaut). Ajout uniquement.
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS show_contact_form boolean NOT NULL DEFAULT true;
ALTER TABLE portfolios ADD COLUMN IF NOT EXISTS show_cv boolean NOT NULL DEFAULT true;
