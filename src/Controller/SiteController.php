<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\Project;
use App\Entity\Realisation;
use App\Service\PortfolioData;
use App\Service\StructuredData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Site public (anciennement le dossier « Portfolio » + l'API Laravel). */
final class SiteController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(PortfolioData $data, \App\Service\StructuredData $schema): Response
    {
        $all = $data->all();

        return $this->render('site/index.html.twig', ['data' => $all, 'jsonld' => $schema->home($all)]);
    }

    #[Route('/projet/{id<\d+>}', name: 'project_show', methods: ['GET'])]
    public function project(Project $project, PortfolioData $data, StructuredData $schema): Response
    {
        return $this->renderWork('site/projet.html.twig', $data->project($project, true), $data, $schema, 'project_show', $project->id);
    }

    #[Route('/realisation/{id<\d+>}', name: 'realisation_show', methods: ['GET'])]
    public function realisation(Realisation $realisation, PortfolioData $data, StructuredData $schema): Response
    {
        return $this->renderWork('site/realisation.html.twig', $data->realisation($realisation, true), $data, $schema, 'realisation_show', $realisation->id);
    }

    /** Page de détail + données structurées « CreativeWork » pour les moteurs. */
    private function renderWork(string $template, array $item, PortfolioData $data, StructuredData $schema, string $route, int $id): Response
    {
        $owner = $data->owner();
        $url = $this->generateUrl($route, ['id' => $id], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render($template, ['item' => $item, 'owner' => $owner, 'jsonld' => $schema->work($item, $owner, $url)]);
    }

    #[Route('/mentions-legales', name: 'legal', methods: ['GET'])]
    public function legal(
        PortfolioData $data,
        #[Autowire('%env(default::SITE_HOST_INFO)%')] ?string $hostInfo,
    ): Response {
        return $this->render('site/legal.html.twig', ['owner' => $data->owner(), 'hostInfo' => $hostInfo]);
    }

    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nAllow: /\n\nSitemap: ".$this->generateUrl('sitemap', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)."\n";

        return new Response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(EntityManagerInterface $em): Response
    {
        $abs = \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL;
        // [url, dernière modification, priorité] : l'accueil (ton nom) est la page la plus importante
        $portfolio = $em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']);
        $urls = [[$this->generateUrl('home', [], $abs), $portfolio?->updatedAt, '1.0']];
        foreach ([
            [Project::class, 'project_show'],
            [Realisation::class, 'realisation_show'],
            [\App\Entity\PersonalProject::class, 'personal_project_show'],
        ] as [$class, $route]) {
            foreach ($em->getRepository($class)->findAll() as $item) {
                $urls[] = [$this->generateUrl($route, ['id' => $item->id], $abs), $item->updatedAt, '0.7'];
            }
        }
        $urls[] = [$this->generateUrl('legal', [], $abs), null, '0.2'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$u, $updated, $priority]) {
            $xml .= '  <url><loc>'.htmlspecialchars($u, \ENT_XML1).'</loc>'
                .($updated ? '<lastmod>'.$updated->format('Y-m-d').'</lastmod>' : '')
                .'<priority>'.$priority.'</priority></url>'."\n";
        }

        return new Response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Ping léger (sans base de données) pour garder l'hébergement éveillé. */
    #[Route('/api/ping', name: 'api_ping', methods: ['GET', 'HEAD'])]
    public function ping(): JsonResponse
    {
        return $this->json(['status' => 'ok', 'message' => 'pong', 'timestamp' => (new \DateTimeImmutable())->format(\DATE_ATOM)]);
    }

    #[Route('/projet-perso/{id<\d+>}', name: 'personal_project_show', methods: ['GET'])]
    public function personalProject(\App\Entity\PersonalProject $project, PortfolioData $data, StructuredData $schema): Response
    {
        // Même page que les réalisations (galerie, liens GitHub / démo, Markdown)
        return $this->renderWork('site/realisation.html.twig', $data->personalProject($project), $data, $schema, 'personal_project_show', $project->id);
    }
}
