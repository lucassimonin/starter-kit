<?php

namespace App\Controller\Admin;

use App\Service\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings')]
class SettingsAdminController extends AbstractController
{
    /** Réglages éditables : clé => [libellé, aide] */
    private const FIELDS = [
        'site_name' => ['Nom du site', 'Utilisé dans le titre des pages et les emails'],
        'tagline' => ['Slogan', 'Suffixe du meta title (ex: Concept restaurant au Marché du Lez)'],
        'logo_url' => ['Logo (URL)', 'Collez une URL depuis la bibliothèque Médias'],
        'contact_email' => ['Email de contact', 'Destinataire par défaut du formulaire de contact'],
        'mailer_from' => ['Email expéditeur', 'Adresse "from" des emails envoyés par le site'],
        'phone' => ['Téléphone', ''],
        'address' => ['Adresse', ''],
        'footer_text' => ['Texte du pied de page', ''],
        'instagram' => ['Instagram (URL)', ''],
        'facebook' => ['Facebook (URL)', ''],
        'analytics_id' => ['ID Analytics', 'Ex: G-XXXXXXX (Google Analytics 4) — vide = désactivé, aucun cookie déposé'],
        'cookie_banner_text' => ['Texte de la barre cookies', 'Vide = texte par défaut. La barre ne s\'affiche que si un ID Analytics est renseigné'],
        'privacy_url' => ['Politique de confidentialité (URL)', 'Lien « En savoir plus » de la barre cookies — vide = /mentions-legales'],
        'maintenance_mode' => ['Mode maintenance', 'Cochez pour fermer temporairement le site aux visiteurs (503) — l\'admin et les admins connectés gardent l\'accès'],
        'maintenance_text' => ['Message de maintenance', 'Affiché aux visiteurs pendant la maintenance'],
    ];

    #[Route('', name: 'admin_settings')]
    public function edit(Request $request, SettingsProvider $settings, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            foreach (array_keys(self::FIELDS) as $key) {
                $settings->set($key, trim((string) $request->request->get($key, '')) ?: null);
            }
            $em->flush();
            $this->addFlash('success', 'Réglages enregistrés.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings/edit.html.twig', [
            'fields' => self::FIELDS,
            'values' => $settings->all(),
        ]);
    }
}
