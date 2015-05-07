@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Creating new collections

  Background:
    Given I am logged into the mobile website
      And I am using the mobile site
      And I have Gather
      And I am on the "Selenium Gather test" page
     When I click the watchstar

  Scenario: New collection overlay interface visible
    Then I see add to new collection button

  Scenario: Inserting a new collection
    When I type "My collection" into the new collection form
     And I click the create collection button
     And I wait
    Then I see the collection editor overlay
