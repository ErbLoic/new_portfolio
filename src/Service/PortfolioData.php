<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Competence;
use App\Entity\Formation;
use App\Entity\ImportantMessage;
use App\Entity\PersonalProject;
use App\Entity\Portfolio;
use App\Entity\Project;
use App\Entity\Realisation;
use App\Entity\Stage;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Construit les données du site public au format JSON de l'ancienne API Laravel,
 * pour que les scripts du front restent identiques.
 */
final class PortfolioData
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly Markdown $md)
    {
    }

    /** Équivalent de l'ancien GET /api/all (+ formations et messages actifs). */
    public function all(): array
    {
        $realisations = $this->em->getRepository(Realisation::class)->findAll();
        $realisationsData = array_map(fn (Realisation $r) => $this->realisation($r), $realisations);

        $byCompany = [];
        foreach ($realisationsData as $r) {
            $byCompany[$r['company_id'] ?? ''][] = $r;
        }

        $portfolio = $this->em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']);
        $messages = array_filter(
            $this->em->getRepository(ImportantMessage::class)->findBy([], ['order' => 'ASC']),
            fn (ImportantMessage $m) => $m->isVisible(),
        );

        return [
            'portfolio' => $portfolio ? $this->portfolio($portfolio) : null,
            'stages' => array_map(fn (Stage $s) => $this->stage($s), $this->em->getRepository(Stage::class)->findAll()),
            'projects' => array_map(fn (Project $p) => $this->project($p), $this->em->getRepository(Project::class)->findAll()),
            'personalProjects' => array_map(
                fn (PersonalProject $p) => $this->personalProject($p),
                $this->em->getRepository(PersonalProject::class)->findBy([], ['order' => 'ASC', 'projectDate' => 'DESC']),
            ),
            'realisations' => $realisationsData,
            'companies' => array_map(fn (Company $c) => $this->company($c), $this->em->getRepository(Company::class)->findAll()),
            'competences' => array_map(fn (Competence $c) => $this->competence($c), $this->em->getRepository(Competence::class)->findAll()),
            'realisationsByCompany' => (object) $byCompany,
            'formations' => array_map(
                fn (Formation $f) => $this->formation($f),
                $this->em->getRepository(Formation::class)->findBy([], ['order' => 'ASC', 'startDate' => 'DESC']),
            ),
            'messages' => array_values(array_map(fn (ImportantMessage $m) => $this->message($m), $messages)),
        ];
    }

    /** Profil du propriétaire du site (titre, logo et aperçu des pages de détail). */
    public function owner(): ?array
    {
        $p = $this->em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']);

        return $p ? $this->portfolio($p) : null;
    }

    public function project(Project $p, bool $withImages = false): array
    {
        $data = [
            'id' => $p->id,
            'company_id' => $p->company?->id,
            'title' => $p->title,
            'type' => $p->type,
            'description' => $p->description,
            'description_html' => $this->md->block($p->description),
            'description_inline' => $this->md->inline($p->description),
            'long_description' => $p->longDescription,
            'year' => $p->year,
            'date' => $p->getMonthInput(),
            'date_label' => $p->getDateLabel(),
            'sort_key' => $p->getSortKey(),
            'github_url' => $p->githubUrl,
            'tags' => array_map(fn ($t) => ['id' => $t->id, 'project_id' => $p->id, 'tag' => $t->tag], $p->tags->toArray()),
            'company' => $p->company ? $this->company($p->company) : null,
            'formation_id' => $p->formation?->id,
            'school' => $p->formation ? $this->school($p->formation) : null,
        ] + $this->timestamps($p);

        // Projet d'école sans entreprise : l'école prend la place de l'entreprise sur la carte
        if (!$data['company'] && $data['school']) {
            $data['company'] = $data['school'];
        }

        if ($withImages) {
            $data['images'] = $this->images($p->images->toArray(), 'project_id', $p->id);
        }

        return $data;
    }

    public function realisation(Realisation $r, bool $withImages = false): array
    {
        $data = [
            'id' => $r->id,
            'type' => $r->type,
            'date' => $r->getMonthInput(),
            'date_label' => $r->getDateLabel(),
            'sort_key' => $r->getSortKey(),
            'company_id' => $r->company?->id,
            'title' => $r->title,
            'description' => $r->description,
            'description_html' => $this->md->block($r->description),
            'description_inline' => $this->md->inline($r->description),
            'long_description' => $r->longDescription,
            'tags' => array_map(fn ($t) => ['id' => $t->id, 'realisation_id' => $r->id, 'tag' => $t->tag], $r->tags->toArray()),
            'company' => $r->company ? $this->company($r->company) : null,
        ] + $this->timestamps($r);

        if ($withImages) {
            $data['images'] = $this->images($r->images->toArray(), 'realisation_id', $r->id);
        }

        return $data;
    }

    /** École au même format qu'une entreprise, pour réutiliser l'affichage existant. */
    private function school(Formation $f): array
    {
        return [
            'id' => 'school-'.$f->id,
            'is_school' => true,
            'name' => $f->school,
            'sector' => $f->title,
            'location' => $f->location,
            'description' => $f->description,
            // Sur les pages projet : la description longue de l'école (sinon la courte)
            'description_html' => $this->md->block($f->longDescription ?: $f->description),
            'photo_url' => $f->logoUrl,
        ];
    }

    public function personalProject(PersonalProject $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            // 'perso' : utilisé par la page de détail ; le type libre reste dans 'category'
            'type' => 'perso',
            'category' => $p->type,
            'description' => $p->description,
            'description_html' => $this->md->block($p->description),
            'description_inline' => $this->md->inline($p->description),
            'long_description' => $p->longDescription,
            'year' => $p->year,
            'date' => $p->getMonthInput(),
            'date_label' => $p->getDateLabel(),
            'sort_key' => $p->getSortKey(),
            'github_url' => $p->githubUrl,
            'demo_url' => $p->demoUrl,
            'order' => $p->order,
            'is_featured' => $p->isFeatured,
            'tags' => array_map(fn ($t) => ['id' => $t->id, 'personal_project_id' => $p->id, 'tag' => $t->tag], $p->tags->toArray()),
            'images' => $this->images($p->images->toArray(), 'personal_project_id', $p->id),
        ] + $this->timestamps($p);
    }

    private function portfolio(Portfolio $p): array
    {
        return [
            'id' => $p->id,
            'first_name' => $p->firstName,
            'last_name' => $p->lastName,
            'bio' => $p->bio,
            'bio_html' => $this->md->block($p->bio),
            'photo_url' => $p->photoUrl,
            'email' => $p->email,
            'phone' => $p->phone,
            'location' => $p->location,
            'linkedin_url' => $p->linkedinUrl,
            'github_url' => $p->githubUrl,
            'year_start' => $p->yearStart,
            'year_end' => $p->yearEnd,
            'contact_message' => $p->contactMessage,
            'contact_message_html' => $this->md->block($p->contactMessage),
            // CV masqué depuis l'admin : l'URL n'est pas du tout envoyée au site
            'cv_url' => $p->showCv ? $p->cvUrl : null,
            'show_contact_form' => $p->showContactForm,
            'job_title' => $p->jobTitle ?: 'Chargé de missions SI en alternance',
        ] + $this->timestamps($p);
    }

    private function company(Company $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'sector' => $c->sector,
            'description' => $c->description,
            'description_html' => $this->md->block($c->description),
            'location' => $c->location,
            'photo_url' => $c->photoUrl,
        ] + $this->timestamps($c);
    }

    private function competence(Competence $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'label' => $c->label,
            'bloc' => $c->bloc,
            'description' => $c->description,
        ] + $this->timestamps($c);
    }

    private function stage(Stage $s): array
    {
        return [
            'id' => $s->id,
            'company_id' => $s->company?->id,
            'start_date' => $this->date($s->startDate),
            'end_date' => $this->date($s->endDate),
            'duration' => $s->duration,
            'type' => $s->type,
            'is_current' => $s->isCurrent,
            'role' => $s->role,
            'description' => $s->description,
            'description_html' => $this->md->block($s->description),
            'company' => $s->company ? $this->company($s->company) : null,
        ] + $this->timestamps($s);
    }

    private function formation(Formation $f): array
    {
        return [
            'id' => $f->id,
            'title' => $f->title,
            'school' => $f->school,
            'location' => $f->location,
            'degree_type' => $f->degreeType,
            'start_date' => $this->date($f->startDate),
            'end_date' => $this->date($f->endDate),
            'is_current' => $f->isCurrent,
            'description' => $f->description,
            'description_html' => $this->md->block($f->description),
            'logo_url' => $f->logoUrl,
            'diploma_url' => $f->diplomaUrl,
            'order' => $f->order,
        ] + $this->timestamps($f);
    }

    private function message(ImportantMessage $m): array
    {
        return [
            'id' => $m->id,
            'title' => $m->title,
            'message' => $m->message,
            'message_html' => $this->md->inline($m->message),
            'type' => $m->type,
            'icon' => $m->icon,
            'link_url' => $m->linkUrl,
            'link_text' => $m->linkText,
            'is_active' => $m->isActive,
            'order' => $m->order,
            'start_date' => $this->date($m->startDate),
            'end_date' => $this->date($m->endDate),
        ] + $this->timestamps($m);
    }

    private function images(array $images, string $fk, ?int $ownerId): array
    {
        return array_map(fn ($i) => [
            'id' => $i->id,
            $fk => $ownerId,
            'image_url' => $i->imageUrl,
            'caption' => $i->caption,
            'description' => $i->description,
            'order' => $i->order,
        ], $images);
    }

    private function timestamps(object $o): array
    {
        return ['created_at' => $this->date($o->createdAt), 'updated_at' => $this->date($o->updatedAt)];
    }

    private function date(?\DateTimeInterface $d): ?string
    {
        return $d?->format(\DateTimeInterface::ATOM);
    }
}
