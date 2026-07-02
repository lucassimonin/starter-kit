<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/media')]
class MediaAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaUploader $uploader,
    ) {
    }

    #[Route('', name: 'admin_media')]
    public function index(): Response
    {
        return $this->render('admin/media/index.html.twig', [
            'medias' => $this->em->getRepository(Media::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/upload', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $this->checkCsrf($request);

        /** @var UploadedFile[] $files */
        $files = $request->files->all('files');
        $alt = trim((string) $request->request->get('alt', ''));
        $count = 0;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            try {
                $media = $this->uploader->upload($file, $alt);
                $this->em->persist($media);
                ++$count;
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        if ($count > 0) {
            $this->em->flush();
            $this->addFlash('success', sprintf('%d image(s) importée(s) — variantes WebP et miniatures générées.', $count));
        }

        return $this->redirectToRoute('admin_media');
    }

    #[Route('/{id}/alt', name: 'admin_media_alt', methods: ['POST'])]
    public function updateAlt(Media $media, Request $request): Response
    {
        $this->checkCsrf($request);
        $media->setAlt(trim((string) $request->request->get('alt', '')));
        $this->em->flush();
        $this->addFlash('success', 'Texte alternatif mis à jour.');

        return $this->redirectToRoute('admin_media');
    }

    #[Route('/{id}/delete', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(Media $media, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->uploader->remove($media);
        $this->em->remove($media);
        $this->em->flush();
        $this->addFlash('success', 'Média supprimé.');

        return $this->redirectToRoute('admin_media');
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
