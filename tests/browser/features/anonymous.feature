Feature: Anonymous users accessing private pages

  Background:
    Given I am not logged in

  Scenario:
    When I visit the Gather page
    Then I see the login page

  Scenario:
    When I visit a private collection page
    Then I see the error page

