@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Add to a collection

  Background:
    Given I am logged into the mobile website
      And I am using the mobile site
      And I have Gather
      And I am on the "Selenium Gather test" page

  Scenario: Check the default watchstar has been replaced
    When I click the watchstar
    Then I see the collection dialog

  Scenario: Adding item to existing collection.
    When I click the watchstar
     And I select a collection
    Then I see a toast panel
