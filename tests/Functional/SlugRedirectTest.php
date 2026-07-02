<?php

namespace App\Tests\Functional;

use App\Entity\Page;
use App\Entity\Redirect;

class SlugRedirectTest extends DatabaseWebTestCase
{
    public function testSlugChangeCreatesA301Redirect(): void
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['slug' => 'mentions-legales']);
        $this->assertNotNull($page);

        $page->setSlug('infos-legales');
        $this->em->flush();

        $redirect = $this->em->getRepository(Redirect::class)->findOneBy(['source' => '/mentions-legales']);
        $this->assertNotNull($redirect, 'Le changement de slug doit créer une redirection');
        $this->assertSame('/infos-legales', $redirect->getTarget());
        $this->assertSame(301, $redirect->getStatusCode());
    }

    public function testOldUrlRedirectsToNewOne(): void
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['slug' => 'mentions-legales']);
        $page->setSlug('infos-legales');
        $this->em->flush();

        $this->client->request('GET', '/mentions-legales');

        $this->assertResponseStatusCodeSame(301);
        $this->assertStringContainsString('/infos-legales', (string) $this->client->getResponse()->headers->get('Location'));
    }
}
