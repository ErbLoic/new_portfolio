<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mois + année de réalisation (projets, réalisations, projets perso).
 * Sert à trier du plus récent au plus ancien et à afficher « Juin 2025 ».
 */
trait ProjectDate
{
    private const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    /** Toujours le 1er du mois. */
    #[ORM\Column(name: 'project_date', type: 'date', nullable: true)]
    public ?\DateTimeInterface $projectDate = null;

    /** Valeur du champ <input type="month"> : « 2025-06 ». */
    public function getMonthInput(): ?string
    {
        return $this->projectDate?->format('Y-m');
    }

    public function setMonthInput(?string $value): void
    {
        $value = trim((string) $value);
        if ('' === $value) {
            $this->projectDate = null;

            return;
        }
        $date = \DateTime::createFromFormat('!Y-m', $value);
        if (!$date || $date->format('Y-m') !== $value) {
            return; // saisie invalide : signalée par la validation du formulaire
        }
        $this->projectDate = $date;
        // Garde l'ancienne colonne « year » cohérente (utilisée par l'ancien affichage)
        if (property_exists($this, 'year')) {
            $this->year = $date->format('Y');
        }
    }

    /** « Juin 2025 », ou l'année seule pour les anciens éléments sans mois. */
    public function getDateLabel(): ?string
    {
        if ($this->projectDate) {
            return ucfirst(self::MONTHS[(int) $this->projectDate->format('n') - 1]).' '.$this->projectDate->format('Y');
        }

        return property_exists($this, 'year') && $this->year ? (string) $this->year : null;
    }

    /** Clé de tri « AAAA-MM » ; sans mois, l'année seule passe après les mois connus de la même année. */
    public function getSortKey(): string
    {
        if ($this->projectDate) {
            return $this->projectDate->format('Y-m');
        }

        return property_exists($this, 'year') && $this->year ? $this->year.'-00' : '0000-00';
    }
}
