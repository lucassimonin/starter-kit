<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages')]
class MessageAdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'admin_messages')]
    public function index(): Response
    {
        return $this->render('admin/message/index.html.twig', [
            'messages' => $this->em->getRepository(ContactMessage::class)->findBy([], ['createdAt' => 'DESC']),
            'unread_count' => $this->em->getRepository(ContactMessage::class)->count(['isRead' => false]),
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_message_toggle', methods: ['POST'])]
    public function toggleRead(ContactMessage $message, Request $request): Response
    {
        $this->checkCsrf($request);
        $message->setIsRead(!$message->isRead());
        $this->em->flush();
        $this->addFlash('success', $message->isRead() ? 'Message marqué comme lu.' : 'Message marqué comme non lu.');

        return $this->redirectToRoute('admin_messages');
    }

    #[Route('/{id}/delete', name: 'admin_message_delete', methods: ['POST'])]
    public function delete(ContactMessage $message, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($message);
        $this->em->flush();
        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('admin_messages');
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
