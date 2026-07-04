<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Actualités : langue par défaut sans préfixe (/actualites),
 * autres langues sous /{locale}/actualites.
 */
class BlogController extends AbstractController
{
    private const PER_PAGE = 9;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/actualites/rss.xml', name: 'front_blog_rss')]
    #[Route('/{_locale}/actualites/rss.xml', name: 'front_blog_rss_locale', requirements: ['_locale' => '%app.extra_locales_pattern%'])]
    public function rss(Request $request): Response
    {
        $response = $this->render('front/blog/rss.xml.twig', [
            'posts' => $this->publishedQuery($request->getLocale())->setMaxResults(20)->getQuery()->getResult(),
        ]);
        $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');

        return $response;
    }

    #[Route('/actualites', name: 'front_blog')]
    #[Route('/{_locale}/actualites', name: 'front_blog_locale', requirements: ['_locale' => '%app.extra_locales_pattern%'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $categorySlug = $request->query->get('categorie');

        $qb = $this->publishedQuery($request->getLocale());
        $activeCategory = null;

        if ($categorySlug) {
            $activeCategory = $this->em->getRepository(PostCategory::class)->findOneBy(['slug' => $categorySlug]);
            if (!$activeCategory) {
                throw $this->createNotFoundException();
            }
            $qb->andWhere('p.category = :category')->setParameter('category', $activeCategory);
        }

        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        $posts = $qb->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()->getResult();

        return $this->render('front/blog/index.html.twig', [
            'posts' => $posts,
            'categories' => $this->em->getRepository(PostCategory::class)->findBy([], ['name' => 'ASC']),
            'active_category' => $activeCategory,
            'current_page' => $page,
            'total_pages' => $pages,
            'current_route' => (string) $request->attributes->get('_route', 'front_blog'),
        ]);
    }

    #[Route('/actualites/{slug}', name: 'front_blog_post', requirements: ['slug' => '[a-z0-9\-]+'])]
    #[Route('/{_locale}/actualites/{slug}', name: 'front_blog_post_locale', requirements: ['_locale' => '%app.extra_locales_pattern%', 'slug' => '[a-z0-9\-]+'])]
    public function post(string $slug, Request $request): Response
    {
        $post = $this->em->getRepository(Post::class)->findOneBy(['slug' => $slug, 'locale' => $request->getLocale()]);

        if (!$post) {
            throw $this->createNotFoundException();
        }

        $isPreview = !$post->isPublished();
        if ($isPreview && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        return $this->render('front/blog/post.html.twig', [
            'post' => $post,
            'is_preview' => $isPreview,
        ]);
    }

    private function publishedQuery(string $locale): \Doctrine\ORM\QueryBuilder
    {
        return $this->em->getRepository(Post::class)->createQueryBuilder('p')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->andWhere('p.locale = :locale')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('locale', $locale)
            ->orderBy('p.publishedAt', 'DESC');
    }
}
