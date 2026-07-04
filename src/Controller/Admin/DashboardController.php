<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Entity\Media;
use App\Entity\Page;
use App\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $pages = $em->getRepository(Page::class)->findBy([], ['updatedAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'recent_pages' => $pages,
            'counts' => [
                'pages' => $em->getRepository(Page::class)->count([]),
                'published' => $em->getRepository(Page::class)->count(['status' => Page::STATUS_PUBLISHED]),
                'media' => $em->getRepository(Media::class)->count([]),
                'redirects' => $em->getRepository(Redirect::class)->count([]),
                'unread_messages' => $em->getRepository(ContactMessage::class)->count(['isRead' => false]),
            ],
        ]);
    }
}
