<?php

namespace App\Controller\Admin;

use App\Block\BlockFormFactory;
use App\Entity\Block;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/blocks')]
class BlockAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlockFormFactory $blockFormFactory,
    ) {
    }

    #[Route('/{id}/save', name: 'admin_block_save', methods: ['POST'])]
    public function save(Block $block, Request $request): Response
    {
        $form = $this->blockFormFactory->create($block);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $block->setData($form->getData());
            $block->getPage()?->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', 'Bloc enregistré.');
        } else {
            $this->addFlash('error', 'Le bloc n\'a pas pu être enregistré, vérifiez les champs.');
        }

        return $this->redirectBack($block);
    }

    #[Route('/{id}/duplicate', name: 'admin_block_duplicate', methods: ['POST'])]
    public function duplicate(Block $block, Request $request): Response
    {
        $this->checkCsrf($request);

        $copy = new Block();
        $copy->setType($block->getType())
            ->setData($block->getData())
            ->setEnabled($block->isEnabled())
            ->setPosition($block->getPosition() + 1)
            ->setPage($block->getPage());

        // Décale les blocs suivants
        foreach ($block->getPage()?->getBlocks() ?? [] as $sibling) {
            if ($sibling->getPosition() > $block->getPosition()) {
                $sibling->setPosition($sibling->getPosition() + 1);
            }
        }

        $this->em->persist($copy);
        $this->em->flush();
        $this->addFlash('success', 'Bloc dupliqué.');

        return $this->redirectBack($copy);
    }

    #[Route('/{id}/toggle', name: 'admin_block_toggle', methods: ['POST'])]
    public function toggle(Block $block, Request $request): Response
    {
        $this->checkCsrf($request);
        $block->setEnabled(!$block->isEnabled());
        $this->em->flush();
        $this->addFlash('success', $block->isEnabled() ? 'Bloc activé.' : 'Bloc masqué (conservé, non affiché sur le site).');

        return $this->redirectBack($block);
    }

    #[Route('/{id}/delete', name: 'admin_block_delete', methods: ['POST'])]
    public function delete(Block $block, Request $request): Response
    {
        $this->checkCsrf($request);
        $pageId = $block->getPage()?->getId();
        $this->em->remove($block);
        $this->em->flush();
        $this->addFlash('success', 'Bloc supprimé.');

        return $this->redirectToRoute('admin_page_edit', ['id' => $pageId]);
    }

    private function redirectBack(Block $block): Response
    {
        return $this->redirectToRoute('admin_page_edit', [
            'id' => $block->getPage()?->getId(),
            '_fragment' => 'bloc-'.$block->getId(),
        ]);
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
