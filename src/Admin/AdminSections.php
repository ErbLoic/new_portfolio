<?php

namespace App\Admin;

use App\Entity;
use App\Form;

/**
 * Déclaration des sections de l'admin : ajouter une section = ajouter une entrée ici.
 *
 * - columns : colonnes affichées dans la liste (propriété => libellé)
 * - files   : fichiers uniques gérés par champ (propriété => [dossier, libellé, document?])
 * - gallery : true si l'entité possède une galerie d'images
 */
final class AdminSections
{
    public const SECTIONS = [
        'entreprises' => [
            'class' => Entity\Company::class, 'form' => Form\CompanyType::class,
            'label' => 'Entreprises', 'new' => 'Ajouter une entreprise', 'icon' => 'bi-building',
            'columns' => ['name' => 'Nom', 'sector' => 'Secteur', 'location' => 'Lieu'],
            'order' => ['name' => 'ASC'],
            'files' => ['photoUrl' => ['folder' => 'companies', 'label' => "Logo de l'entreprise"]],
        ],
        'experiences' => [
            'class' => Entity\Stage::class, 'form' => Form\StageType::class,
            'label' => 'Expériences', 'new' => 'Ajouter une expérience', 'icon' => 'bi-briefcase',
            'columns' => ['role' => 'Poste', 'type' => 'Type', 'company' => 'Entreprise', 'formation' => 'Formation', 'startDate' => 'Début', 'isCurrent' => 'En cours'],
            'order' => ['startDate' => 'DESC'],
        ],
        'missions' => [
            'class' => Entity\Mission::class, 'form' => Form\MissionType::class,
            'label' => 'Missions', 'new' => 'Ajouter une mission', 'icon' => 'bi-list-check',
            'columns' => ['title' => 'Mission', 'stage' => 'Expérience', 'order' => 'Ordre', 'isPublished' => 'Visible'],
            'order' => ['order' => 'ASC', 'id' => 'DESC'],
            'toggle' => 'isPublished',
        ],
        'formations' => [
            'class' => Entity\Formation::class, 'form' => Form\FormationType::class,
            'label' => 'Formations', 'new' => 'Ajouter une formation', 'icon' => 'bi-mortarboard',
            'columns' => ['title' => 'Intitulé', 'school' => 'Établissement', 'startDate' => 'Début', 'isCurrent' => 'En cours', 'order' => 'Ordre'],
            'order' => ['order' => 'ASC', 'startDate' => 'DESC'],
            'files' => [
                'logoUrl' => ['folder' => 'formations', 'label' => "Logo de l'école"],
                'diplomaUrl' => ['folder' => 'diplomas', 'label' => 'Diplôme (PDF ou image)', 'document' => true],
            ],
        ],
        'projets' => [
            'class' => Entity\Project::class, 'form' => Form\ProjectType::class,
            'label' => 'Projets', 'new' => 'Ajouter un projet', 'icon' => 'bi-kanban',
            'columns' => ['title' => 'Titre', 'type' => 'Type', 'dateLabel' => 'Date', 'company' => 'Entreprise', 'formation' => 'École', 'images' => 'Images'],
            'order' => ['projectDate' => 'DESC', 'id' => 'DESC'],
            'gallery' => true, 'public' => 'project_show',
        ],
        'realisations' => [
            'class' => Entity\Realisation::class, 'form' => Form\RealisationType::class,
            'label' => 'Réalisations', 'new' => 'Ajouter une réalisation', 'icon' => 'bi-palette',
            'columns' => ['title' => 'Titre', 'type' => 'Type', 'dateLabel' => 'Date', 'company' => 'Entreprise', 'images' => 'Images'],
            'order' => ['projectDate' => 'DESC', 'id' => 'DESC'],
            'gallery' => true, 'public' => 'realisation_show',
        ],
        'projets-perso' => [
            'class' => Entity\PersonalProject::class, 'form' => Form\PersonalProjectType::class,
            'label' => 'Projets perso', 'new' => 'Ajouter un projet perso', 'icon' => 'bi-rocket-takeoff',
            'columns' => ['title' => 'Titre', 'type' => 'Type', 'dateLabel' => 'Date', 'isFeatured' => 'Mis en avant', 'images' => 'Images'],
            'order' => ['order' => 'ASC', 'projectDate' => 'DESC'],
            'gallery' => true, 'public' => 'personal_project_show',
        ],
        'competences' => [
            'class' => Entity\Competence::class, 'form' => Form\CompetenceType::class,
            'label' => 'Compétences', 'new' => 'Ajouter une compétence', 'icon' => 'bi-stars',
            'columns' => ['label' => 'Compétence', 'bloc' => 'Catégorie', 'level' => 'Niveau'],
            'order' => ['bloc' => 'ASC', 'level' => 'DESC', 'label' => 'ASC'],
        ],
        'certifications' => [
            'class' => Entity\Certification::class, 'form' => Form\CertificationType::class,
            'label' => 'Certifications', 'new' => 'Ajouter une certification', 'icon' => 'bi-patch-check',
            'columns' => ['name' => 'Certification', 'issuer' => 'Organisme', 'issuedAt' => 'Obtenue le', 'order' => 'Ordre'],
            'order' => ['order' => 'ASC', 'issuedAt' => 'DESC'],
            'files' => ['badgeUrl' => ['folder' => 'certifications', 'label' => 'Badge (image)']],
        ],
        'messages' => [
            'class' => Entity\ImportantMessage::class, 'form' => Form\MessageType::class,
            'label' => 'Messages', 'new' => 'Ajouter un message', 'icon' => 'bi-megaphone',
            'columns' => ['title' => 'Titre', 'message' => 'Message', 'type' => 'Style', 'status' => 'Visibilité'],
            'order' => ['order' => 'ASC'],
            'toggle' => 'isActive',
        ],
    ];

    public static function get(string $slug): array
    {
        return self::SECTIONS[$slug] ?? throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Section inconnue');
    }
}
