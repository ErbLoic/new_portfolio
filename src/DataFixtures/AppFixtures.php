<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Competence;
use App\Entity\Formation;
use App\Entity\ImportantMessage;
use App\Entity\PersonalProject;
use App\Entity\Portfolio;
use App\Entity\Project;
use App\Entity\Realisation;
use App\Entity\Stage;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données FICTIVES pour le développement local uniquement.
 * Compte admin de test : admin@example.test / admin-local-test-1234
 */
final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->name = 'Admin';
        $admin->email = 'admin@example.test';
        $admin->role = 'admin';
        $admin->password = $this->hasher->hashPassword($admin, 'admin-local-test-1234');
        $manager->persist($admin);

        $p = new Portfolio();
        $p->firstName = 'Camille';
        $p->lastName = 'Exemple';
        $p->bio = "Étudiant·e en alternance, passionné·e par le développement web et les systèmes d'information.";
        $p->email = 'contact@example.test';
        $p->location = 'Paris, France';
        $p->githubUrl = 'https://github.com/example';
        $p->contactMessage = "N'hésitez pas à me contacter pour un stage ou une alternance.";
        $manager->persist($p);

        $comps = [];
        foreach ([['B1.1', 'Gérer le patrimoine informatique', 'B1'], ['B1.2', 'Répondre aux incidents', 'B1'], ['B1.4', 'Travailler en mode projet', 'B1'], ['B2.1', 'Concevoir une solution applicative', 'B2']] as [$code, $label, $bloc]) {
            $c = new Competence();
            $c->code = $code;
            $c->label = $label;
            $c->bloc = $bloc;
            $manager->persist($c);
            $comps[] = $c;
        }

        $company = new Company();
        $company->name = 'Société Démo';
        $company->sector = 'Services numériques';
        $company->description = 'Entreprise fictive pour les tests.';
        $company->location = 'Lyon';
        $manager->persist($company);

        $stage = new Stage();
        $stage->company = $company;
        $stage->role = 'Développeur·se web stagiaire';
        $stage->startDate = new \DateTime('2025-01-06');
        $stage->endDate = new \DateTime('2025-02-14');
        $stage->duration = '6 semaines';
        $stage->description = "Développement d'un outil interne en Symfony.";
        $stage->competences->add($comps[0]);
        $stage->competences->add($comps[3]);
        $manager->persist($stage);

        $f = new Formation();
        $f->title = 'BTS SIO SLAM';
        $f->degreeType = 'BTS';
        $f->school = 'Lycée Démo';
        $f->location = 'Paris';
        $f->startDate = new \DateTime('2024-09-01');
        $f->isCurrent = true;
        $manager->persist($f);

        $proj = new Project();
        $proj->title = 'Application de gestion';
        $proj->type = 'Application Web';
        $proj->year = '2025';
        $proj->company = $company;
        $proj->description = 'Projet fictif de démonstration.';
        $proj->longDescription = "## Contexte\nProjet **fictif** pour vérifier le rendu Markdown.";
        $proj->setTagsText('PHP, Symfony, PostgreSQL');
        $proj->competences->add($comps[2]);
        $proj->competences->add($comps[3]);
        $manager->persist($proj);

        $r = new Realisation();
        $r->title = 'Refonte du site vitrine';
        $r->type = 'stage';
        $r->company = $company;
        $r->description = 'Réalisation fictive pendant le stage.';
        $r->setTagsText('HTML, CSS, JavaScript');
        $manager->persist($r);

        $pp = new PersonalProject();
        $pp->title = 'Bot Discord';
        $pp->type = 'Script';
        $pp->year = '2024';
        $pp->setTagsText('Node.js');
        $manager->persist($pp);

        $m = new ImportantMessage();
        $m->title = 'Recherche de stage';
        $m->message = 'Je recherche un stage de 6 semaines à partir de janvier.';
        $m->type = 'info';
        $manager->persist($m);

        $manager->flush();
    }
}
