<?php

namespace App\EventListener;

use App\Entity\Page;
use App\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Sur une 404 :
 * 1. consulte la table des redirections (gestionnaire admin + historique de slugs)
 * 2. sinon, rend la page « erreur-404 » éditable dans l'admin (si elle existe)
 */
class RedirectSubscriber implements EventSubscriberInterface
{
    public const NOT_FOUND_SLUG = 'erreur-404';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Environment $twig,
        private readonly string $appDefaultLocale,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();
        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        // Pas de page 404 custom pour l'admin
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/_')) {
            return;
        }

        // 1. Redirection connue ?
        $redirect = $this->em->getRepository(Redirect::class)->findOneBy(['source' => $path]);
        if ($redirect) {
            $event->setResponse(new RedirectResponse($redirect->getTarget(), $redirect->getStatusCode()));

            return;
        }

        // 2. Page 404 éditable (dans la langue de la requête, sinon langue par défaut)
        $repo = $this->em->getRepository(Page::class);
        $page = $repo->findOneBy(['slug' => self::NOT_FOUND_SLUG, 'locale' => $request->getLocale(), 'status' => Page::STATUS_PUBLISHED])
            ?? $repo->findOneBy(['slug' => self::NOT_FOUND_SLUG, 'locale' => $this->appDefaultLocale, 'status' => Page::STATUS_PUBLISHED]);

        if (!$page) {
            return; // page d'erreur Symfony par défaut
        }

        $html = $this->twig->render('front/page.html.twig', [
            'page' => $page,
            'is_preview' => false,
        ]);

        $event->setResponse(new Response($html, Response::HTTP_NOT_FOUND));
    }
}
