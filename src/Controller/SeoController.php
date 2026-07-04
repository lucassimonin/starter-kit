<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/sitemap.xml', name: 'front_sitemap')]
    public function sitemap(): Response
    {
        $pages = $this->em->getRepository(Page::class)->findBy(
            ['status' => Page::STATUS_PUBLISHED, 'noindex' => false],
            ['updatedAt' => 'DESC'],
        );

        $posts = $this->em->getRepository(Post::class)->createQueryBuilder('p')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()->getResult();

        $response = $this->render('front/sitemap.xml.twig', ['pages' => $pages, 'posts' => $posts]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    #[Route('/robots.txt', name: 'front_robots')]
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: '.$this->generateUrl('front_sitemap', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
