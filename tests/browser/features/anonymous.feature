@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Anonymous users

  Background:
    Given I am not logged in
      And I am using the mobile site

  Scenario: Gather redirects to login screen
    When I visit the Gather page
    Then I see the login page

  Scenario: Anons not allowed to view private collections
    When I visit a private collection page
    Then I see the error page

  Scenario: Anons see watchstar and CTA
    Given I am in alpha mode
      And I am on the "Selenium Gather test" page
    When I click the watchstar
    Then I see the anonymous CTA
