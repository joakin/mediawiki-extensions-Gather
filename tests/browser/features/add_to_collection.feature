@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Add to a collection

  Background:
    Given I am logged into the mobile website
      And I am using the mobile site
      And I have Gather

  Scenario: Check the default watchstar has been replaced
    Given I am on the "Selenium Gather test" page
    When I click the watchstar
    Then I see the collection dialog

  Scenario: Adding item to existing collection.
    Given I have a collection
     And I am on the "Selenium Gather test" page
    When I click the watchstar
     And I select a collection
    Then I see a toast panel

  Scenario:
    Given I have more than 100 collections
      And I am on the "Selenium Gather test" page
    When I click the watchstar
     Then I see a more button
