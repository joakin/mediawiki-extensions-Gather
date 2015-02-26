Feature: Anonymous users accessing private pages

  Scenario:
    Given I am not logged in
    When I visit the Gather page
    Then I see the login page
