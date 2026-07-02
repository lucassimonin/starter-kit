<?php

namespace App\EventListener;

use App\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sur une 404, consulte la table des redirections
 * (gestionnaire admin + historique de slugs) avant de rendre l'erreur.
 */
class RedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
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

        $path = rtrim($event->getRequest()->getPathInfo(), '/') ?: '/';

        $redirect = $this->em->getRepository(Redirect::class)->findOneBy(['source' => $path]);
        if (!$redirect) {
            return;
        }

        $event->setResponse(new RedirectResponse($redirect->getTarget(), $redirect->getStatusCode()));
    }
}
