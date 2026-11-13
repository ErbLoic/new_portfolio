<?php

namespace App\Controller\Admin;

use App\Admin\AdminSections;
use App\Entity\Portfolio;
use App\Entity\Project;
use App\Entity\Realisation;
use App\Form\PortfolioType;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class DashboardController extends AbstractController
{
    private const PORTFOLIO_FILES = [
        'photoUrl' => ['folder' => 'portfolio', 'label' => 'Photo de profil', 'document' => false],
        'cvUrl' => ['folder' => 'portfolio', 'label' => 'CV (PDF)', 'document' => true],
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin/connexion', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $auth): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/login.html.twig', [
            'last_username' => $auth->getLastUsername(),
            'error' => $auth->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/deconnexion', name: 'admin_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le pare-feu.');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $counts = [];
        foreach (AdminSections::SECTIONS as $slug => $conf) {
            $counts[$slug] = $this->em->getRepository($conf['class'])->count([]);
        }

        return $this->render('admin/dashboard.html.twig', [
            'counts' => $counts,
            'recentProjects' => $this->em->getRepository(Project::class)->findBy([], ['id' => 'DESC'], 5),
            'recentRealisations' => $this->em->getRepository(Realisation::class)->findBy([], ['id' => 'DESC'], 5),
            'portfolio' => $this->em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/profil', name: 'admin_portfolio', methods: ['GET', 'POST'])]
    public function portfolio(Request $request, FileStorage $storage): Response
    {
        $portfolio = $this->em->getRepository(Portfolio::class)->findOneBy([], ['id' => 'ASC']) ?? new Portfolio();

        $form = $this->createForm(PortfolioType::class, $portfolio);
        foreach (self::PORTFOLIO_FILES as $prop => $file) {
            $form->add('upload_'.$prop, FileType::class, [
                'label' => $file['label'], 'mapped' => false, 'required' => false,
                'attr' => ['accept' => $file['document'] ? '.pdf' : 'image/*'],
            ]);
            if ($portfolio->$prop) {
                $form->add('remove_'.$prop, CheckboxType::class, ['label' => 'Retirer le fichier actuel', 'mapped' => false, 'required' => false]);
            }
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ok = true;
            foreach (self::PORTFOLIO_FILES as $prop => $file) {
                $upload = $form->get('upload_'.$prop)->getData();
                if ($upload) {
                    try {
                        $url = $storage->upload($upload, $file['folder'], $file['document']);
                        $storage->delete($portfolio->$prop);
                        $portfolio->$prop = $url;
                    } catch (\Throwable $e) {
                        $form->get('upload_'.$prop)->addError(new FormError($e->getMessage()));
                        $ok = false;
                    }
                } elseif ($form->has('remove_'.$prop) && $form->get('remove_'.$prop)->getData()) {
                    $storage->delete($portfolio->$prop);
                    $portfolio->$prop = null;
                }
            }
            if ($ok) {
                $this->em->persist($portfolio);
                $this->em->flush();
                $this->addFlash('success', 'Profil enregistré.');

                return $this->redirectToRoute('admin_portfolio');
            }
        }

        return $this->render('admin/portfolio.html.twig', ['form' => $form, 'portfolio' => $portfolio]);
    }
}
