<?php

namespace App\Controller;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Rendu front des pages. La langue par défaut est servie sans préfixe (/contact),
 * les autres langues sous /{locale}/… (/en/contact).
 */
class PageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $appDefaultLocale,
    ) {
    }

    #[Route('/', name: 'front_homepage')]
    public function homepage(Request $request): Response
    {
        return $this->renderHomepage($this->appDefaultLocale, $request);
    }

    #[Route('/{_locale}', name: 'front_homepage_locale', requirements: ['_locale' => '%app.extra_locales_pattern%'], priority: 5)]
    public function homepageLocale(Request $request): Response
    {
        return $this->renderHomepage($request->getLocale(), $request);
    }

    #[Route('/{_locale}/{slug}', name: 'front_page_locale', requirements: ['_locale' => '%app.extra_locales_pattern%', 'slug' => '[a-z0-9\-\/]+'], priority: -5)]
    public function pageLocale(string $slug, Request $request): Response
    {
        return $this->renderBySlug($slug, $request->getLocale(), $request);
    }

    #[Route('/{slug}', name: 'front_page', requirements: ['slug' => '[a-z0-9\-\/]+'], priority: -10)]
    public function page(string $slug, Request $request): Response
    {
        return $this->renderBySlug($slug, $this->appDefaultLocale, $request);
    }

    private function renderHomepage(string $locale, Request $request): Response
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['isHomepage' => true, 'locale' => $locale]);

        if (!$page) {
            throw $this->createNotFoundException(sprintf('Aucune page d\'accueil pour la langue « %s ».', $locale));
        }

        return $this->renderPage($page, $request);
    }

    private function renderBySlug(string $slug, string $locale, Request $request): Response
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['slug' => $slug, 'locale' => $locale]);

        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage($page, $request);
    }

    private function renderPage(Page $page, Request $request): Response
    {
        $isPreview = !$page->isPublished();

        if ($isPreview) {
            // Accessible aux admins connectés OU via le lien de prévisualisation partageable
            $hasValidToken = hash_equals($page->getPreviewToken(), (string) $request->query->get('preview', ''));
            if (!$hasValidToken && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createNotFoundException();
            }
        }

        return $this->render('front/page.html.twig', [
            'page' => $page,
            'is_preview' => $isPreview,
        ]);
    }
}
