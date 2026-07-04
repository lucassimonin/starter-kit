<?php

namespace App\Tests\Functional;

use App\Entity\Post;
use App\Entity\Redirect;

class BlogTest extends DatabaseWebTestCase
{
    public function testBlogIndexListsPublishedPosts(): void
    {
        $this->client->request('GET', '/actualites');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Actualités');
        $this->assertSelectorTextContains('body', 'Soirée jazz sur la terrasse');
        // Le brouillon n'apparaît pas
        $this->assertSelectorTextNotContains('body', 'Atelier workshop : entretenir son café racer');
    }

    public function testCategoryFilter(): void
    {
        $this->client->request('GET', '/actualites?categorie=cote-cuisine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'La carte d\'été est arrivée');
        $this->assertSelectorTextNotContains('body', 'Soirée jazz');
    }

    public function testPostPageWithSeo(): void
    {
        $this->client->request('GET', '/actualites/soiree-jazz-terrasse-jeudis');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Soirée jazz');
        $this->assertSelectorExists('meta[property="og:type"][content="article"]');
        $this->assertSelectorExists('script[type="application/ld+json"]');
    }

    public function testDraftPostIs404ForVisitors(): void
    {
        $this->client->request('GET', '/actualites/atelier-workshop-cafe-racer');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRssFeed(): void
    {
        $this->client->request('GET', '/actualites/rss.xml');

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<rss', $content);
        $this->assertStringContainsString('Soirée jazz', $content);
        $this->assertStringNotContainsString('Atelier workshop', $content);
    }

    public function testSitemapIncludesPosts(): void
    {
        $this->client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/actualites', $content);
        $this->assertStringContainsString('soiree-jazz-terrasse-jeudis', $content);
    }

    public function testPostSlugChangeCreatesRedirect(): void
    {
        $post = $this->em->getRepository(Post::class)->findOneBy(['slug' => 'carte-ete-arrivee']);
        $post->setSlug('nouvelle-carte-ete');
        $this->em->flush();

        $redirect = $this->em->getRepository(Redirect::class)->findOneBy(['source' => '/actualites/carte-ete-arrivee']);
        $this->assertNotNull($redirect);
        $this->assertSame('/actualites/nouvelle-carte-ete', $redirect->getTarget());
    }
}
