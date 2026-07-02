Feature: Site public
  In order to discover the restaurant
  As a visitor
  I need to browse the published pages

  Scenario: The homepage displays the hero and the menu
    When I am on "/"
    Then the response status code should be 200
    And I should see "Le BIVOUAK"
    And I should see "Carte du moment"
    And I should see "Nous trouver"

  Scenario: The legal page is accessible
    When I am on "/mentions-legales"
    Then the response status code should be 200
    And I should see "Mentions légales"

  Scenario: An unknown page returns a 404
    When I am on "/page-qui-nexiste-pas"
    Then the response status code should be 404

  Scenario: The sitemap is generated
    When I am on "/sitemap.xml"
    Then the response status code should be 200
    And the response should contain "urlset"
