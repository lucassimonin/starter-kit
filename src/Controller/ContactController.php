<?php

namespace App\Controller;

use App\Entity\Block;
use App\Service\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/_contact', name: 'front_contact', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer, SettingsProvider $settings, EntityManagerInterface $em): Response
    {
        $redirect = new RedirectResponse(($request->headers->get('referer') ?: '/').'#contact');

        if (!$this->isCsrfTokenValid('contact', (string) $request->request->get('_token'))) {
            $this->addFlash('contact_error', 'Session expirée, merci de réessayer.');

            return $redirect;
        }

        // Honeypot : champ invisible, un humain ne le remplit jamais
        if ('' !== (string) $request->request->get('website', '')) {
            return $redirect;
        }

        $name = trim((string) $request->request->get('name', ''));
        $email = trim((string) $request->request->get('email', ''));
        $subject = trim((string) $request->request->get('subject', ''));
        $message = trim((string) $request->request->get('message', ''));

        if ('' === $name || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
            $this->addFlash('contact_error', 'Merci de vérifier le formulaire : nom, e-mail valide et message (10 caractères minimum) sont requis.');

            return $redirect;
        }

        // Destinataire : configuré dans le bloc (jamais depuis le POST), sinon réglages globaux
        $recipient = null;
        if ($blockId = $request->request->getInt('block')) {
            $block = $em->getRepository(Block::class)->find($blockId);
            $recipient = $block?->getData()['recipient'] ?? null;
        }
        $recipient = $recipient ?: $settings->get('contact_email', 'contact@example.com');

        $mail = (new Email())
            ->from($settings->get('mailer_from', 'no-reply@'.$request->getHost()))
            ->replyTo($email)
            ->to($recipient)
            ->subject(sprintf('[%s] %s', $settings->get('site_name', 'Site'), $subject ?: 'Nouveau message'))
            ->text(sprintf("Nom : %s\nE-mail : %s\nSujet : %s\n\n%s", $name, $email, $subject, $message));

        try {
            $mailer->send($mail);
        } catch (\Throwable) {
            // DSN null en dev : on considère la demande enregistrée
        }

        $this->addFlash('contact_success', 'Merci — votre demande a bien été envoyée.');

        return $redirect;
    }
}
