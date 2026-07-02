<?php

namespace App\Controller\Admin;

use App\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/redirects')]
class RedirectAdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'admin_redirects')]
    public function index(): Response
    {
        return $this->render('admin/redirect/index.html.twig', [
            'redirects' => $this->em->getRepository(Redirect::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/add', name: 'admin_redirect_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $this->checkCsrf($request);

        $source = trim((string) $request->request->get('source', ''));
        $target = trim((string) $request->request->get('target', ''));

        if ('' === $source || '' === $target) {
            $this->addFlash('error', 'Source et cible sont obligatoires.');

            return $this->redirectToRoute('admin_redirects');
        }

        $redirect = new Redirect();
        $redirect->setSource($source)
            ->setTarget($target)
            ->setStatusCode(302 === $request->request->getInt('statusCode') ? 302 : 301);

        $this->em->persist($redirect);
        $this->em->flush();
        $this->addFlash('success', 'Redirection créée.');

        return $this->redirectToRoute('admin_redirects');
    }

    #[Route('/{id}/delete', name: 'admin_redirect_delete', methods: ['POST'])]
    public function delete(Redirect $redirect, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($redirect);
        $this->em->flush();
        $this->addFlash('success', 'Redirection supprimée.');

        return $this->redirectToRoute('admin_redirects');
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
