<?php

namespace App\Tests\Functional;

use App\Entity\Page;
use App\Entity\PageRevision;
use App\Entity\Setting;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Multilingue, prévisualisation partageable, duplication, 404 éditable,
 * maintenance, export/import, historique.
 */
class FeaturesTest extends DatabaseWebTestCase
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

    private function adminCsrfToken(): string
    {
        $crawler = $this->client->request('GET', '/admin/pages');

        return (string) $crawler->filter('input[name="_token"]')->first()->attr('value');
    }

    // ---- Multilingue ----

    public function testEnglishHomepageIsServedUnderEnPrefix(): void
    {
        $this->client->request('GET', '/en');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Book a table');
    }

    public function testFrenchHomepageHasHreflangAlternates(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('link[rel="alternate"][hreflang="en"]');
        $this->assertSelectorExists('link[rel="alternate"][hreflang="x-default"]');
    }

    public function testEnglishBlogListingExists(): void
    {
        $this->client->request('GET', '/en/actualites');

        $this->assertResponseIsSuccessful();
    }

    // ---- Prévisualisation partageable ----

    public function testDraftPageAccessibleWithPreviewToken(): void
    {
        $draft = new Page();
        $draft->setTitle('Page secrète')->setSlug('page-secrete')->setStatus(Page::STATUS_DRAFT);
        $this->em->persist($draft);
        $this->em->flush();

        // Sans token : 404 (la page 404 éditable est rendue)
        $this->client->request('GET', '/page-secrete');
        $this->assertResponseStatusCodeSame(404);

        // Avec le token : visible, avec le bandeau de prévisualisation
        $this->client->request('GET', '/page-secrete?preview='.$draft->getPreviewToken());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Prévisualisation');
    }

    // ---- Duplication ----

    public function testPageDuplication(): void
    {
        $this->logIn();
        $homepage = $this->em->getRepository(Page::class)->findOneBy(['isHomepage' => true, 'locale' => 'fr']);

        $this->client->request('POST', '/admin/pages/'.$homepage->getId().'/duplicate', [
            '_token' => $this->adminCsrfToken(),
        ]);

        $this->assertResponseRedirects();
        $copy = $this->em->getRepository(Page::class)->findOneBy(['title' => $homepage->getTitle().' (copie)']);
        $this->assertNotNull($copy);
        $this->assertSame(Page::STATUS_DRAFT, $copy->getStatus());
        $this->assertCount(\count($homepage->getBlocks()), $copy->getBlocks());
    }

    // ---- Traduction ----

    public function testCreateTranslationFromExistingPage(): void
    {
        $this->logIn();
        $legal = $this->em->getRepository(Page::class)->findOneBy(['slug' => 'mentions-legales']);

        $this->client->request('POST', '/admin/pages/'.$legal->getId().'/translate/en', [
            '_token' => $this->adminCsrfToken(),
        ]);

        $this->assertResponseRedirects();
        $translation = $this->em->getRepository(Page::class)->findOneBy([
            'translationGroup' => $legal->getTranslationGroup(),
            'locale' => 'en',
        ]);
        $this->assertNotNull($translation);
        $this->assertSame(Page::STATUS_DRAFT, $translation->getStatus());
    }

    // ---- 404 éditable ----

    public function testCustom404PageIsRendered(): void
    {
        $this->client->request('GET', '/nimporte-quoi-inexistant');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('body', 'Vous vous êtes égaré en chemin');
    }

    // ---- Mode maintenance ----

    public function testMaintenanceModeReturns503ButAdminStaysAccessible(): void
    {
        $this->em->persist((new Setting())->setKey('maintenance_mode')->setValue('1'));
        $this->em->flush();

        $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(503);
        $this->assertSelectorTextContains('body', 'revient très vite');

        $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();
    }

    // ---- Export / import ----

    public function testExportThenImportRecreatesThePage(): void
    {
        $this->logIn();
        $homepage = $this->em->getRepository(Page::class)->findOneBy(['isHomepage' => true, 'locale' => 'fr']);

        $this->client->request('GET', '/admin/pages/'.$homepage->getId().'/export');
        $this->assertResponseIsSuccessful();
        $json = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('"blocks"', $json);

        $tmp = tempnam(sys_get_temp_dir(), 'page').'.json';
        file_put_contents($tmp, $json);

        $this->client->request('POST', '/admin/pages/import', ['_token' => $this->adminCsrfToken()], [
            'file' => new UploadedFile($tmp, 'page.json', 'application/json', null, true),
        ]);

        $this->assertResponseRedirects();
        $imported = $this->em->getRepository(Page::class)->createQueryBuilder('p')
            ->where('p.title LIKE :t')->setParameter('t', '%(import)')
            ->getQuery()->getOneOrNullResult();
        $this->assertNotNull($imported);
        $this->assertCount(\count($homepage->getBlocks()), $imported->getBlocks());
    }

    // ---- Historique / restauration ----

    public function testReorderCreatesRevisionAndRestoreBringsOrderBack(): void
    {
        $this->logIn();
        $homepage = $this->em->getRepository(Page::class)->findOneBy(['isHomepage' => true, 'locale' => 'fr']);
        $originalFirstType = $homepage->getBlocks()->first()->getType();

        // Récupère le jeton CSRF du builder puis inverse l'ordre des blocs
        $crawler = $this->client->request('GET', '/admin/pages/'.$homepage->getId());
        $csrf = (string) $crawler->filter('#blocks-list')->attr('data-csrf');
        $ids = array_reverse($crawler->filter('[data-block-id]')->each(fn ($node) => $node->attr('data-block-id')));

        $this->client->request('POST', '/admin/pages/'.$homepage->getId().'/reorder', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-CSRF-Token' => $csrf,
        ], json_encode(['order' => $ids]));
        $this->assertResponseIsSuccessful();

        $revision = $this->em->getRepository(PageRevision::class)->findOneBy(['page' => $homepage], ['createdAt' => 'DESC']);
        $this->assertNotNull($revision, 'La réorganisation doit créer une révision');

        // Restaure : le premier bloc redevient celui d'origine
        $this->client->request('POST', '/admin/pages/'.$homepage->getId().'/revisions/'.$revision->getId().'/restore', [
            '_token' => $this->adminCsrfToken(),
        ]);
        $this->assertResponseRedirects();

        $this->em->clear();
        $reloaded = $this->em->getRepository(Page::class)->find($homepage->getId());
        $blocks = $reloaded->getBlocks()->toArray();
        usort($blocks, fn ($a, $b) => $a->getPosition() <=> $b->getPosition());
        $this->assertSame($originalFirstType, $blocks[0]->getType());
    }
}
