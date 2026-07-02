<?php

namespace App\Controller\Admin;

use App\Block\BlockFormFactory;
use App\Block\BlockRegistry;
use App\Entity\Block;
use App\Entity\Page;
use App\Form\PageType;
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
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlockRegistry $registry,
        private readonly BlockFormFactory $blockFormFactory,
        private readonly SluggerInterface $slugger,
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
        foreach ($this->em->getRepository(Page::class)->findBy(['isHomepage' => true]) as $other) {
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
