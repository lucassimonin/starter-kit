<?php

namespace App\Tests\Behat;

use App\DataFixtures\AppFixtures;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Contexte Behat : réinitialise la base (SQLite) avec les fixtures
 * avant chaque scénario + étapes métier réutilisables.
 */
class FeatureContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    /** @BeforeScenario */
    public function resetDatabase(BeforeScenarioScope $scope): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        (new AppFixtures($this->hasher))->load($this->em);
        $this->em->clear();
    }

    /**
     * @Given I am authenticated as admin
     * @Given je suis connecté en tant qu'admin
     */
    public function iAmAuthenticatedAsAdmin(): void
    {
        $session = $this->getSession();
        $session->visit('/admin/login');

        $page = $session->getPage();
        $page->fillField('_username', 'admin@agence.fr');
        $page->fillField('_password', 'admin');
        $page->pressButton('Se connecter');
    }

    /**
     * @Then the response should be a :code redirect to :path
     */
    public function theResponseShouldBeARedirectTo(int $code, string $path): void
    {
        $headers = $this->getSession()->getResponseHeaders();
        $location = $headers['location'][0] ?? $headers['Location'][0] ?? '';

        if (!str_contains($location, $path)) {
            throw new \RuntimeException(sprintf('Redirection attendue vers "%s", obtenu "%s".', $path, $location));
        }
    }
}
