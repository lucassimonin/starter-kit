<?php

namespace App\Tests\Functional;

class FrontSmokeTest extends DatabaseWebTestCase
{
    public function testHomepageDisplaysHero(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Le BIVOUAK');
    }

    public function testHomepageContainsSeoTags(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('meta[name="description"]');
        $this->assertSelectorExists('link[rel="canonical"]');
        $this->assertSelectorExists('script[type="application/ld+json"]');
    }

    public function testLegalPageIsAccessibleAndNoindex(): void
    {
        $this->client->request('GET', '/mentions-legales');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('meta[name="robots"][content="noindex, nofollow"]');
    }

    public function testSitemapListsPublishedIndexablePages(): void
    {
        $this->client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<urlset', $content);
        // La page mentions légales est noindex : absente du sitemap
        $this->assertStringNotContainsString('mentions-legales', $content);
    }

    public function testUnknownPageReturns404(): void
    {
        $this->client->request('GET', '/page-inexistante');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testContactFormRejectsHoneypot(): void
    {
        $crawler = $this->client->request('GET', '/');
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/_contact', [
            '_token' => $token,
            'name' => 'Robot',
            'email' => 'robot@spam.tld',
            'message' => 'Un message de robot spammeur',
            'website' => 'https://spam.tld', // honeypot rempli
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        // Pas de message de succès : la soumission a été ignorée silencieusement
        $this->assertSelectorNotExists('[role="status"]');
    }
}
