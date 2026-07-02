Feature: Administration
  In order to manage the website content
  As an agency client
  I need a secured and usable admin panel

  Scenario: Anonymous visitors cannot access the admin
    When I go to "/admin"
    Then I should be on "/admin/login"

  Scenario: An admin can log in and see the dashboard
    Given I am authenticated as admin
    When I go to "/admin"
    Then the response status code should be 200
    And I should see "Tableau de bord"

  Scenario: The page builder lists the Bivouak blocks
    Given I am authenticated as admin
    When I go to "/admin/pages"
    And I follow "BIVOUAK CAFÉ — Concept restaurant au Marché du Lez"
    Then the response status code should be 200
    And I should see "Ajouter un bloc"
    And I should see "Hero plein écran"
    And I should see "Carte / menu (tarifs)"

  Scenario: An admin can create a redirect
    Given I am authenticated as admin
    When I go to "/admin/redirects"
    And I fill in "source" with "/ancienne-url"
    And I fill in "target" with "/"
    And I press "Créer"
    Then I should see "Redirection créée."
