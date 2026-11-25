<?php

namespace App\Controller\Admin;

use App\Admin\AdminSections;
use App\Entity\GalleryOwner;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** CRUD générique de toutes les sections déclarées dans AdminSections. */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/{section}', requirements: ['section' => 'entreprises|experiences|missions|formations|projets|realisations|projets-perso|competences|certifications|messages'])]
final class CrudController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $storage,
    ) {
    }

    #[Route('', name: 'admin_list', methods: ['GET'])]
    public function list(string $section): Response
    {
        $conf = AdminSections::get($section);

        return $this->render('admin/list.html.twig', [
            'section' => $section,
            'conf' => $conf,
            'items' => $this->em->getRepository($conf['class'])->findBy([], $conf['order']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_new', methods: ['GET', 'POST'])]
    public function new(string $section, Request $request): Response
    {
        $conf = AdminSections::get($section);

        return $this->handleForm($section, $conf, new $conf['class'](), $request);
    }

    #[Route('/{id<\d+>}', name: 'admin_edit', methods: ['GET', 'POST'])]
    public function edit(string $section, int $id, Request $request): Response
    {
        $conf = AdminSections::get($section);

        return $this->handleForm($section, $conf, $this->find($conf, $id), $request);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'admin_delete', methods: ['POST'])]
    public function delete(string $section, int $id, Request $request): Response
    {
        $conf = AdminSections::get($section);
        $item = $this->find($conf, $id);
        if (!$this->isCsrfTokenValid('delete'.$section.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach (array_keys($conf['files'] ?? []) as $prop) {
            $this->storage->delete($item->$prop);
        }
        if ($item instanceof GalleryOwner) {
            foreach ($item->getImages() as $img) {
                $this->storage->delete($img->imageUrl);
            }
        }
        $this->em->remove($item);
        $this->em->flush();
        $this->addFlash('success', 'Élément supprimé.');

        return $this->redirectToRoute('admin_list', ['section' => $section]);
    }

    #[Route('/{id<\d+>}/basculer', name: 'admin_toggle', methods: ['POST'])]
    public function toggle(string $section, int $id, Request $request): Response
    {
        $conf = AdminSections::get($section);
        $prop = $conf['toggle'] ?? throw $this->createNotFoundException();
        $item = $this->find($conf, $id);
        if ($this->isCsrfTokenValid('toggle'.$section.$id, $request->request->getString('_token'))) {
            $item->$prop = !$item->$prop;
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_list', ['section' => $section]);
    }

    /** Ajout d'images (plusieurs à la fois) dans la galerie. */
    #[Route('/{id<\d+>}/images', name: 'admin_gallery_upload', methods: ['POST'])]
    public function galleryUpload(string $section, int $id, Request $request): Response
    {
        $item = $this->galleryOwner($section, $id, $request, 'gallery');
        $files = array_filter((array) $request->files->get('images', []));
        $count = 0;
        foreach ($files as $file) {
            try {
                $item->newImage($this->storage->upload($file, $this->galleryFolder($section)));
                ++$count;
            } catch (\Throwable $e) {
                $this->addFlash('error', $file->getClientOriginalName().' : '.$e->getMessage());
            }
        }
        $this->em->flush();
        if ($count) {
            $this->addFlash('success', sprintf('%d image(s) ajoutée(s).', $count));
        }

        return $this->redirectToRoute('admin_edit', ['section' => $section, 'id' => $id, '_fragment' => 'galerie']);
    }

    /** Enregistre légendes, descriptions, ordre et suppressions de la galerie en une fois. */
    #[Route('/{id<\d+>}/images/enregistrer', name: 'admin_gallery_save', methods: ['POST'])]
    public function gallerySave(string $section, int $id, Request $request): Response
    {
        $item = $this->galleryOwner($section, $id, $request, 'gallery');
        $data = $request->request->all('img');
        foreach ($item->getImages()->toArray() as $img) {
            $row = $data[$img->id] ?? null;
            if (!$row) {
                continue;
            }
            if (!empty($row['delete'])) {
                $this->storage->delete($img->imageUrl);
                $item->getImages()->removeElement($img);
                continue;
            }
            $img->caption = trim($row['caption'] ?? '') ?: null;
            $img->description = trim($row['description'] ?? '') ?: null;
            $img->order = (int) ($row['order'] ?? 0);
        }
        $this->em->flush();
        $this->addFlash('success', 'Galerie enregistrée.');

        return $this->redirectToRoute('admin_edit', ['section' => $section, 'id' => $id, '_fragment' => 'galerie']);
    }

    private function handleForm(string $section, array $conf, object $item, Request $request): Response
    {
        $form = $this->createForm($conf['form'], $item);
        $this->addFileFields($form, $conf, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->applyFiles($form, $conf, $item)) {
                $isNew = null === $item->id;
                $this->em->persist($item);
                $this->em->flush();
                $this->addFlash('success', $isNew ? 'Élément créé.' : 'Modifications enregistrées.');

                // Après création d'un élément avec galerie, on reste sur la fiche pour ajouter les images
                return !empty($conf['gallery'])
                    ? $this->redirectToRoute('admin_edit', ['section' => $section, 'id' => $item->id])
                    : $this->redirectToRoute('admin_list', ['section' => $section]);
            }
        }

        return $this->render('admin/form.html.twig', [
            'section' => $section,
            'conf' => $conf,
            'item' => $item,
            'form' => $form,
        ]);
    }

    private function addFileFields(FormInterface $form, array $conf, object $item): void
    {
        foreach ($conf['files'] ?? [] as $prop => $file) {
            $form->add('upload_'.$prop, FileType::class, [
                'label' => $file['label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => !empty($file['document']) ? '.pdf,image/*' : 'image/*'],
            ]);
            if ($item->$prop) {
                $form->add('remove_'.$prop, CheckboxType::class, ['label' => 'Retirer le fichier actuel', 'mapped' => false, 'required' => false]);
            }
        }
    }

    /** @return bool false si un envoi de fichier a échoué (erreur ajoutée au formulaire) */
    private function applyFiles(FormInterface $form, array $conf, object $item): bool
    {
        foreach ($conf['files'] ?? [] as $prop => $file) {
            /** @var UploadedFile|null $upload */
            $upload = $form->get('upload_'.$prop)->getData();
            if ($upload) {
                try {
                    $url = $this->storage->upload($upload, $file['folder'], !empty($file['document']));
                } catch (\Throwable $e) {
                    $form->get('upload_'.$prop)->addError(new \Symfony\Component\Form\FormError($e->getMessage()));

                    return false;
                }
                $this->storage->delete($item->$prop);
                $item->$prop = $url;
            } elseif ($form->has('remove_'.$prop) && $form->get('remove_'.$prop)->getData()) {
                $this->storage->delete($item->$prop);
                $item->$prop = null;
            }
        }

        return true;
    }

    private function find(array $conf, int $id): object
    {
        return $this->em->find($conf['class'], $id) ?? throw $this->createNotFoundException();
    }

    private function galleryOwner(string $section, int $id, Request $request, string $tokenId): GalleryOwner
    {
        $conf = AdminSections::get($section);
        $item = $this->find($conf, $id);
        if (!$item instanceof GalleryOwner || !$this->isCsrfTokenValid($tokenId.$section.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        return $item;
    }

    private function galleryFolder(string $section): string
    {
        return 'realisations' === $section ? 'realisations' : 'projects';
    }
}
