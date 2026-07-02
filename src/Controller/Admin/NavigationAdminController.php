<?php

namespace App\Controller\Admin;

use App\Entity\NavigationItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/navigation')]
class NavigationAdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'admin_navigation')]
    public function index(): Response
    {
        return $this->render('admin/navigation/index.html.twig', [
            'header_items' => $this->em->getRepository(NavigationItem::class)->findBy(['location' => NavigationItem::LOCATION_HEADER], ['position' => 'ASC']),
            'footer_items' => $this->em->getRepository(NavigationItem::class)->findBy(['location' => NavigationItem::LOCATION_FOOTER], ['position' => 'ASC']),
        ]);
    }

    #[Route('/add', name: 'admin_navigation_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $this->checkCsrf($request);

        $item = new NavigationItem();
        $this->hydrate($item, $request);
        $this->em->persist($item);
        $this->em->flush();
        $this->addFlash('success', 'Lien ajouté au menu.');

        return $this->redirectToRoute('admin_navigation');
    }

    #[Route('/{id}/update', name: 'admin_navigation_update', methods: ['POST'])]
    public function update(NavigationItem $item, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->hydrate($item, $request);
        $this->em->flush();
        $this->addFlash('success', 'Lien mis à jour.');

        return $this->redirectToRoute('admin_navigation');
    }

    #[Route('/{id}/delete', name: 'admin_navigation_delete', methods: ['POST'])]
    public function delete(NavigationItem $item, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($item);
        $this->em->flush();
        $this->addFlash('success', 'Lien supprimé.');

        return $this->redirectToRoute('admin_navigation');
    }

    private function hydrate(NavigationItem $item, Request $request): void
    {
        $location = (string) $request->request->get('location', NavigationItem::LOCATION_HEADER);

        $item->setLabel(trim((string) $request->request->get('label', '')))
            ->setUrl(trim((string) $request->request->get('url', '')))
            ->setLocation(\in_array($location, [NavigationItem::LOCATION_HEADER, NavigationItem::LOCATION_FOOTER], true) ? $location : NavigationItem::LOCATION_HEADER)
            ->setPosition($request->request->getInt('position'))
            ->setIsButton($request->request->getBoolean('isButton'));
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
