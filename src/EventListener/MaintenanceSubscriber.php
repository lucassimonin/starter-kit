<?php

namespace App\EventListener;

use App\Service\SettingsProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Mode maintenance : activable dans Réglages (maintenance_mode).
 * Le front renvoie 503 ; l'admin reste accessible et les admins
 * connectés voient le site normalement.
 */
class MaintenanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettingsProvider $settings,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité 6 : après le firewall (8), pour pouvoir tester le rôle
        return [KernelEvents::REQUEST => ['onKernelRequest', 6]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ('1' !== $this->settings->get('maintenance_mode')) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/_')) {
            return;
        }

        try {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                return; // les admins voient le site
            }
        } catch (\Throwable) {
            // pas de contexte de sécurité : visiteur anonyme
        }

        $html = $this->twig->render('front/maintenance.html.twig');
        $event->setResponse(new Response($html, Response::HTTP_SERVICE_UNAVAILABLE, ['Retry-After' => '3600']));
    }
}
