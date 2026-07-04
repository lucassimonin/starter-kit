<?php

namespace App\Controller\Admin;

use App\Block\BlockFormFactory;
use App\Block\BlockRegistry;
use App\Entity\Block;
use App\Entity\Page;
use App\Entity\PageRevision;
use App\Form\PageType;
use App\Service\RevisionRecorder;
use App\Service\SeoChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/pages')]
class PageAdminController extends AbstractController
{
    /** @param list<string> $appLocales */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlockRegistry $registry,
        private readonly BlockFormFactory $blockFormFactory,
        private readonly SluggerInterface $slugger,
        private readonly SeoChecker $seoChecker,
        private readonly RevisionRecorder $revisions,
        private readonly array $appLocales,
    ) {
    }

    #[Route('', name: 'admin_pages')]
    public function index(): Response
    {
        return $this->render('admin/page/index.html.twig', [
            'pages' => $this->em->getRepository(Page::class)->findBy([], ['updatedAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_page_new')]
    public function new(Request $request): Response
    {
        $page = new Page();
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeSlug($page);
            $this->ensureSingleHomepage($page);
            $this->em->persist($page);
            $this->em->flush();
            $this->addFlash('success', 'Page créée — ajoutez maintenant des blocs.');

            return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
        }

        return $this->render('admin/page/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_page_edit', requirements: ['id' => '\d+'])]
    public function edit(Page $page, Request $request): Response
    {
        $settingsForm = $this->createForm(PageType::class, $page);
        $settingsForm->handleRequest($request);

        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $this->normalizeSlug($page);
            $this->ensureSingleHomepage($page);
            $this->em->flush();
            $this->addFlash('success', 'Réglages de la page enregistrés.');

            return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
        }

        $blockForms = [];
        foreach ($page->getBlocks() as $block) {
            $blockForms[$block->getId()] = $this->blockFormFactory->create($block)->createView();
        }

        return $this->render('admin/page/edit.html.twig', [
            'page' => $page,
            'settings_form' => $settingsForm,
            'block_forms' => $blockForms,
            'block_types' => $this->registry->all(),
            'seo_checks' => $this->seoChecker->check($page),
            'seo_score' => $this->seoChecker->score($page),
            'locales' => $this->appLocales,
            'translations' => $this->em->getRepository(Page::class)->findBy(['translationGroup' => $page->getTranslationGroup()], ['locale' => 'ASC']),
            'revisions' => $this->em->getRepository(PageRevision::class)->findBy(['page' => $page], ['createdAt' => 'DESC'], 10),
        ]);
    }

    #[Route('/{id}/blocks/add', name: 'admin_page_block_add', methods: ['POST'])]
    public function addBlock(Page $page, Request $request): Response
    {
        $this->checkCsrf($request);
        $type = (string) $request->request->get('type');

        if (!$this->registry->has($type)) {
            throw $this->createNotFoundException('Type de bloc inconnu.');
        }

        $this->revisions->record($page, 'Avant ajout du bloc '.$type);

        $block = new Block();
        $block->setType($type)
            ->setData($this->registry->get($type)->getDefaultData())
            ->setPosition(\count($page->getBlocks()));
        $page->addBlock($block);
        $page->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($block);
        $this->em->flush();

        $this->addFlash('success', sprintf('Bloc « %s » ajouté en bas de page.', $this->registry->get($type)->getLabel()));

        return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId(), '_fragment' => 'bloc-'.$block->getId()]);
    }

    #[Route('/{id}/reorder', name: 'admin_page_reorder', methods: ['POST'])]
    public function reorder(Page $page, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['ok' => false, 'error' => 'CSRF'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        $order = $payload['order'] ?? [];

        if (!\is_array($order)) {
            return new JsonResponse(['ok' => false], 400);
        }

        $this->revisions->record($page, 'Avant réorganisation des blocs');

        $blocksById = [];
        foreach ($page->getBlocks() as $block) {
            $blocksById[$block->getId()] = $block;
        }

        foreach (array_values($order) as $position => $id) {
            if (isset($blocksById[(int) $id])) {
                $blocksById[(int) $id]->setPosition($position);
            }
        }

        $page->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/translate/{locale}', name: 'admin_page_translate', methods: ['POST'])]
    public function translate(Page $page, string $locale, Request $request): Response
    {
        $this->checkCsrf($request);

        if (!\in_array($locale, $this->appLocales, true)) {
            throw $this->createNotFoundException('Langue inconnue.');
        }

        $existing = $this->em->getRepository(Page::class)->findOneBy([
            'translationGroup' => $page->getTranslationGroup(),
            'locale' => $locale,
        ]);
        if ($existing) {
            return $this->redirectToRoute('admin_page_edit', ['id' => $existing->getId()]);
        }

        $copy = $this->clonePage($page);
        $copy->setLocale($locale)
            ->setTranslationGroup($page->getTranslationGroup())
            ->setIsHomepage($page->isHomepage()); // une accueil par langue

        $this->em->persist($copy);
        $this->em->flush();
        $this->addFlash('success', sprintf('Version %s créée en brouillon — traduisez les contenus puis publiez.', strtoupper($locale)));

        return $this->redirectToRoute('admin_page_edit', ['id' => $copy->getId()]);
    }

    #[Route('/{id}/duplicate', name: 'admin_page_duplicate', methods: ['POST'])]
    public function duplicate(Page $page, Request $request): Response
    {
        $this->checkCsrf($request);

        $copy = $this->clonePage($page);
        $copy->setTitle($page->getTitle().' (copie)')
            ->setSlug($page->getSlug().'-copie-'.substr(bin2hex(random_bytes(2)), 0, 4))
            ->setLocale($page->getLocale());

        $this->em->persist($copy);
        $this->em->flush();
        $this->addFlash('success', 'Page dupliquée en brouillon (blocs et SEO inclus).');

        return $this->redirectToRoute('admin_page_edit', ['id' => $copy->getId()]);
    }

    /** Copie complète : contenu, blocs et SEO — statut brouillon, nouveau groupe de traduction */
    private function clonePage(Page $page): Page
    {
        $copy = new Page();
        $copy->setTitle($page->getTitle())
            ->setSlug($page->getSlug())
            ->setStatus(Page::STATUS_DRAFT)
            ->setMetaTitle($page->getMetaTitle())
            ->setMetaDescription($page->getMetaDescription())
            ->setOgImage($page->getOgImage())
            ->setCanonicalUrl(null)
            ->setNoindex($page->isNoindex())
            ->setStructuredData($page->getStructuredData());

        foreach ($page->getBlocks() as $block) {
            $blockCopy = new Block();
            $blockCopy->setType($block->getType())
                ->setData($block->getData())
                ->setPosition($block->getPosition())
                ->setEnabled($block->isEnabled());
            $copy->addBlock($blockCopy);
        }

        return $copy;
    }

    #[Route('/{id}/revisions/{revisionId}/restore', name: 'admin_page_revision_restore', methods: ['POST'], requirements: ['id' => '\d+', 'revisionId' => '\d+'])]
    public function restoreRevision(Page $page, int $revisionId, Request $request): Response
    {
        $this->checkCsrf($request);

        $revision = $this->em->getRepository(PageRevision::class)->find($revisionId);
        if (!$revision || $revision->getPage() !== $page) {
            throw $this->createNotFoundException('Révision introuvable pour cette page.');
        }

        $this->revisions->restore($page, $revision);
        $this->em->flush();
        $this->addFlash('success', sprintf('Contenu restauré à l\'état du %s.', $revision->getCreatedAt()->format('d/m/Y H:i')));

        return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
    }

    #[Route('/{id}/export', name: 'admin_page_export', requirements: ['id' => '\d+'])]
    public function export(Page $page): Response
    {
        $payload = [
            'version' => 1,
            'title' => $page->getTitle(),
            'locale' => $page->getLocale(),
            'seo' => [
                'metaTitle' => $page->getMetaTitle(),
                'metaDescription' => $page->getMetaDescription(),
                'ogImage' => $page->getOgImage(),
                'noindex' => $page->isNoindex(),
                'structuredData' => $page->getStructuredData(),
            ],
            'blocks' => array_map(static fn (Block $block) => [
                'type' => $block->getType(),
                'position' => $block->getPosition(),
                'enabled' => $block->isEnabled(),
                'data' => $block->getData(),
            ], $page->getBlocks()->toArray()),
        ];

        $response = new JsonResponse($payload);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="page-%s.json"', $page->getSlug() ?: $page->getId()));

        return $response;
    }

    #[Route('/import', name: 'admin_page_import', methods: ['POST'])]
    public function import(Request $request): Response
    {
        $this->checkCsrf($request);

        $file = $request->files->get('file');
        $payload = $file ? json_decode((string) file_get_contents($file->getPathname()), true) : null;

        if (!\is_array($payload) || !isset($payload['blocks']) || !\is_array($payload['blocks'])) {
            $this->addFlash('error', 'Fichier invalide — exportez une page depuis un projet starter kit.');

            return $this->redirectToRoute('admin_pages');
        }

        $page = new Page();
        $page->setTitle(($payload['title'] ?? 'Page importée').' (import)')
            ->setSlug('import-'.substr(bin2hex(random_bytes(4)), 0, 8))
            ->setLocale(\in_array($payload['locale'] ?? '', $this->appLocales, true) ? $payload['locale'] : $this->appLocales[0])
            ->setStatus(Page::STATUS_DRAFT)
            ->setMetaTitle($payload['seo']['metaTitle'] ?? null)
            ->setMetaDescription($payload['seo']['metaDescription'] ?? null)
            ->setOgImage($payload['seo']['ogImage'] ?? null)
            ->setNoindex((bool) ($payload['seo']['noindex'] ?? false))
            ->setStructuredData($payload['seo']['structuredData'] ?? null);

        $skipped = 0;
        foreach ($payload['blocks'] as $blockData) {
            $type = (string) ($blockData['type'] ?? '');
            if (!$this->registry->has($type)) {
                ++$skipped; // type de bloc absent de ce projet

                continue;
            }
            $block = new Block();
            $block->setType($type)
                ->setPosition((int) ($blockData['position'] ?? 0))
                ->setEnabled((bool) ($blockData['enabled'] ?? true))
                ->setData(\is_array($blockData['data'] ?? null) ? $blockData['data'] : []);
            $page->addBlock($block);
        }

        $this->em->persist($page);
        $this->em->flush();

        $message = 'Page importée en brouillon — pensez à ajuster le slug.';
        if ($skipped > 0) {
            $message .= sprintf(' %d bloc(s) ignoré(s) : type inconnu dans ce projet (créez le type de bloc puis réimportez).', $skipped);
        }
        $this->addFlash('success', $message);

        return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_page_delete', methods: ['POST'])]
    public function delete(Page $page, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($page);
        $this->em->flush();
        $this->addFlash('success', 'Page supprimée.');

        return $this->redirectToRoute('admin_pages');
    }

    private function normalizeSlug(Page $page): void
    {
        $slug = $page->getSlug() ?: $page->getTitle();
        $page->setSlug(strtolower($this->slugger->slug($slug)->toString()));
    }

    private function ensureSingleHomepage(Page $page): void
    {
        if (!$page->isHomepage()) {
            return;
        }
        // Une seule page d'accueil par langue
        foreach ($this->em->getRepository(Page::class)->findBy(['isHomepage' => true, 'locale' => $page->getLocale()]) as $other) {
            if ($other !== $page) {
                $other->setIsHomepage(false);
            }
        }
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
