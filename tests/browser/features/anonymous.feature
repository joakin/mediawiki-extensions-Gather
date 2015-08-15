@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Anonymous users

  Background:
    Given I am not logged in
      And I am using the mobile site
      And there are public collections

  Scenario: Gather shows public view
    When I visit the Gather page
    Then I see the recent collections page

  Scenario: Clicking tab shows login page
    When I visit the Gather page
        And I click the my collections tab
    Then I see the login page

  Scenario: Anons not allowed to view private collections
    When I visit a private collection page
    Then I see the error page

  Scenario: Anons see watchstar and CTA
    Given I have Gather
      And I am on the "Selenium Gather test" page
    When I click the watchstar
    Then I see the anonymous CTA

  Scenario: Anons can see my public collection
    When I visit a public collection
    Then I can see items in the collection
