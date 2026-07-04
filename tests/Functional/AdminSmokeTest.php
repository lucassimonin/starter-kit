<?php

namespace App\Tests\Functional;

use App\Entity\ContactMessage;

class AdminSmokeTest extends DatabaseWebTestCase
{
    private function logIn(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@agence.fr',
            '_password' => 'admin',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(302);
        $this->assertStringContainsString('/admin/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testAdminCanLogInAndSeeDashboard(): void
    {
        $this->logIn();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord');
    }

    public function testPageBuilderListsBlocks(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/pages');
        $link = $crawler->filter('table a')->first()->attr('href');
        $this->client->request('GET', $link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#blocks-list');
        $this->assertSelectorTextContains('body', 'Ajouter un bloc');
    }

    public function testCollectionPrototypeIsValidHtml(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/pages');
        $link = $crawler->filter('table a')->first()->attr('href');
        $crawler = $this->client->request('GET', $link);

        // Prototype des collections (cartes, catégories de menu) :
        // une fois décodé, il doit contenir du vrai HTML, pas des entités
        // doublement échappées (régression : HTML affiché en texte à l'ajout)
        $prototype = $crawler->filter('[data-collection]')->first()->attr('data-prototype');

        $this->assertNotEmpty($prototype);
        $this->assertStringContainsString('<', $prototype, 'Le prototype doit contenir du HTML décodable');
        $this->assertStringNotContainsString('&lt;', $prototype, 'Le prototype ne doit pas être doublement échappé');
        $this->assertStringContainsString('__name__', $prototype, 'Le prototype doit contenir le placeholder d\'index');
    }

    public function testAdminNewFeaturesArePresent(): void
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/admin/pages');
        $link = $crawler->filter('table a')->first()->attr('href');
        $this->client->request('GET', $link);

        $this->assertResponseIsSuccessful();
        // Sélecteur de médias : modale + boutons "Parcourir"
        $this->assertSelectorExists('#mediaPicker');
        $this->assertSelectorExists('[data-media-picker]');
        // Checklist SEO : score affiché
        $this->assertSelectorTextContains('body', '/100');

        // Module actualités accessible
        $this->client->request('GET', '/admin/posts');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Soirée jazz');
    }

    public function testContactMessagesAreListedInTheBackOffice(): void
    {
        $message = (new ContactMessage())
            ->setName('Jeanne Client')
            ->setEmail('jeanne@exemple.fr')
            ->setSubject('Question')
            ->setMessage('Bonjour, ceci est un message de test.')
            ->setRecipient('contact@exemple.fr');
        $this->em->persist($message);
        $this->em->flush();

        $this->logIn();
        $this->client->request('GET', '/admin/messages');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Jeanne Client');
        $this->assertSelectorTextContains('body', 'Non lu');
    }
}
