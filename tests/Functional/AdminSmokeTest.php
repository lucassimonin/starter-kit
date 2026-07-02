<?php

namespace App\Tests\Functional;

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
}
