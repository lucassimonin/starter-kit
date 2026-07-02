<?php

namespace App\Controller;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/', name: 'front_homepage')]
    public function homepage(): Response
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['isHomepage' => true]);

        if (!$page) {
            throw $this->createNotFoundException('Aucune page d\'accueil définie. Chargez les fixtures ou créez une page dans l\'admin.');
        }

        return $this->renderPage($page);
    }

    #[Route('/{slug}', name: 'front_page', requirements: ['slug' => '[a-z0-9\-\/]+'], priority: -10)]
    public function page(string $slug): Response
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['slug' => $slug]);

        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage($page);
    }

    private function renderPage(Page $page): Response
    {
        $isPreview = !$page->isPublished();

        if ($isPreview && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        return $this->render('front/page.html.twig', [
            'page' => $page,
            'is_preview' => $isPreview,
        ]);
    }
}
