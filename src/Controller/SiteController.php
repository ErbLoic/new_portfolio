<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\Project;
use App\Entity\Realisation;
use App\Service\PortfolioData;
use App\Service\StructuredData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /** Formulaire de contact (appelé en JSON par app.js). */
    #[Route('/contact', name: 'contact', methods: ['POST'])]
    public function contact(
        Request $request,
        ValidatorInterface $validator,
        // Le mailer n'est construit qu'à l'envoi (dans le try) : une config invalide renvoie un JSON propre
        #[\Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure(MailerInterface::class)] \Closure $mailer,
        EntityManagerInterface $em,
        #[Autowire('%env(default::MAILER_FROM)%')] ?string $mailerFrom,
        ?\Psr\Log\LoggerInterface $logger = null,
    ): JsonResponse {
        $payload = $request->toArray();
        $input = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? '')),
            'message' => trim((string) ($payload['message'] ?? '')),
        ];

        $violations = $validator->validate($input, new Assert\Collection([
            'name' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            'message' => [new Assert\NotBlank(), new Assert\Length(max: 5000)],
        ]));
        if (count($violations) > 0) {
            return $this->json(['success' => false, 'error' => 'Formulaire invalide : vérifiez les champs.'], 422);
        }

        $owner = $em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']);
        // Formulaire désactivé dans l'admin : on refuse aussi les envois directs
        if ($owner && !$owner->showContactForm) {
            return $this->json(['success' => false, 'error' => 'Le formulaire de contact est désactivé.'], 403);
        }
        $to = $owner?->email ?: $mailerFrom;
        if (!$to || !$mailerFrom) {
            return $this->json(['success' => false, 'error' => 'Adresse email de destination non configurée'], 500);
        }

        try {
            $mailer()->send((new TemplatedEmail())
                ->to($to)
                ->replyTo(new Address($input['email'], $input['name']))
                ->from(new Address($mailerFrom, 'Portfolio — formulaire de contact'))
                ->subject('📬 Nouveau message de '.$input['name'])
                ->htmlTemplate('emails/contact.html.twig')
                ->textTemplate('emails/contact.txt.twig')
                ->context([
                    'senderName' => $input['name'],
                    'senderEmail' => $input['email'],
                    'messageBody' => $input['message'],
                    'sentAt' => new \DateTimeImmutable(),
                    'siteUrl' => $this->generateUrl('home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                    'ownerFirstName' => $owner?->firstName,
                ]));
        } catch (\Throwable $e) {
            $logger?->error('Envoi du formulaire de contact impossible : {message}', ['message' => $e->getMessage()]);

            return $this->json(['success' => false, 'error' => "Le message n'a pas pu être envoyé. Réessayez plus tard ou écrivez-moi directement par email."], 500);
        }

        return $this->json(['success' => true, 'message' => 'Message envoyé avec succès']);
    }
}
