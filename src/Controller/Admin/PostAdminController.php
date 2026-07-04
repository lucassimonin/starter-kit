<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\PostCategory;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/posts')]
class PostAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'admin_posts')]
    public function index(): Response
    {
        return $this->render('admin/post/index.html.twig', [
            'posts' => $this->em->getRepository(Post::class)->findBy([], ['updatedAt' => 'DESC']),
            'categories' => $this->em->getRepository(PostCategory::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_post_new')]
    #[Route('/{id}/edit', name: 'admin_post_edit', requirements: ['id' => '\d+'])]
    public function form(Request $request, ?Post $post = null): Response
    {
        $post ??= new Post();
        $isNew = null === $post->getId();

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $post->getSlug() ?: $post->getTitle();
            $post->setSlug(strtolower($this->slugger->slug($slug)->toString()));

            $this->em->persist($post);
            $this->em->flush();
            $this->addFlash('success', $isNew ? 'Article créé (brouillon) — publiez-le quand il est prêt.' : 'Article enregistré.');

            return $this->redirectToRoute('admin_post_edit', ['id' => $post->getId()]);
        }

        return $this->render('admin/post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
            'is_new' => $isNew,
        ]);
    }

    #[Route('/{id}/publish', name: 'admin_post_publish', methods: ['POST'])]
    public function publish(Post $post, Request $request): Response
    {
        $this->checkCsrf($request);
        $post->setPublishedAt($post->isPublished() ? null : new \DateTimeImmutable());
        $this->em->flush();
        $this->addFlash('success', $post->isPublished() ? 'Article publié.' : 'Article repassé en brouillon.');

        return $this->redirectToRoute('admin_posts');
    }

    #[Route('/{id}/delete', name: 'admin_post_delete', methods: ['POST'])]
    public function delete(Post $post, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($post);
        $this->em->flush();
        $this->addFlash('success', 'Article supprimé.');

        return $this->redirectToRoute('admin_posts');
    }

    #[Route('/categories/add', name: 'admin_post_category_add', methods: ['POST'])]
    public function addCategory(Request $request): Response
    {
        $this->checkCsrf($request);
        $name = trim((string) $request->request->get('name', ''));

        if ('' !== $name) {
            $category = (new PostCategory())
                ->setName($name)
                ->setSlug(strtolower($this->slugger->slug($name)->toString()));
            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', 'Catégorie créée.');
        }

        return $this->redirectToRoute('admin_posts');
    }

    #[Route('/categories/{id}/delete', name: 'admin_post_category_delete', methods: ['POST'])]
    public function deleteCategory(PostCategory $category, Request $request): Response
    {
        $this->checkCsrf($request);
        $this->em->remove($category);
        $this->em->flush();
        $this->addFlash('success', 'Catégorie supprimée (les articles associés restent, sans catégorie).');

        return $this->redirectToRoute('admin_posts');
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
