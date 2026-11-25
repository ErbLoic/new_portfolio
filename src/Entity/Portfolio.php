<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'portfolios'), ORM\HasLifecycleCallbacks]
class Portfolio
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column(name: 'first_name')] public string $firstName = '';
    #[ORM\Column(name: 'last_name')] public string $lastName = '';
    #[ORM\Column(type: 'text', nullable: true)] public ?string $bio = null;
    #[ORM\Column(name: 'photo_url', nullable: true)] public ?string $photoUrl = null;
    #[ORM\Column(nullable: true)] public ?string $email = null;
    #[ORM\Column(nullable: true)] public ?string $phone = null;
    #[ORM\Column(nullable: true)] public ?string $location = null;
    #[ORM\Column(name: 'linkedin_url', nullable: true)] public ?string $linkedinUrl = null;
    #[ORM\Column(name: 'github_url', nullable: true)] public ?string $githubUrl = null;
    #[ORM\Column(name: 'year_start')] public int $yearStart = 2024;
    #[ORM\Column(name: 'year_end')] public int $yearEnd = 2026;
    #[ORM\Column(name: 'contact_message', type: 'text', nullable: true)] public ?string $contactMessage = null;
    #[ORM\Column(name: 'cv_url', nullable: true)] public ?string $cvUrl = null;
    /** Titre affiché sous le nom (ex. « Chargé de missions SI en alternance »). */
    #[ORM\Column(name: 'job_title', nullable: true)] public ?string $jobTitle = null;
    /** Afficher le bouton et la section « Télécharger mon CV ». */
    #[ORM\Column(name: 'show_cv', options: ['default' => true])] public bool $showCv = true;
    /** Affichage du parcours : « split » (deux frises) ou « merged » (une seule frise). */
    #[ORM\Column(name: 'timeline_mode', length: 10, options: ['default' => 'split'])] public string $timelineMode = 'split';
    /** Frise : point de départ (affiché tout en bas). */
    #[ORM\Column(name: 'timeline_start', type: 'text', nullable: true)] public ?string $timelineStart = null;
    /** Frise : « Et ensuite ? » (objectif, affiché tout en haut). */
    #[ORM\Column(name: 'timeline_next', type: 'text', nullable: true)] public ?string $timelineNext = null;
    /** Terminal caché : texte de « cat motivation.txt ». */
    #[ORM\Column(name: 'terminal_motivation', nullable: true)] public ?string $terminalMotivation = null;
    /** Terminal caché : phrase par défaut de « cowsay ». */
    #[ORM\Column(name: 'terminal_cowsay', nullable: true)] public ?string $terminalCowsay = null;
    /** Terminal caché : citations de « fortune », une par ligne. */
    #[ORM\Column(name: 'terminal_fortunes', type: 'text', nullable: true)] public ?string $terminalFortunes = null;
    /** Terminal caché : commandes perso, une par ligne au format « commande | réponse ». */
    #[ORM\Column(name: 'terminal_commands', type: 'text', nullable: true)] public ?string $terminalCommands = null;
}
