@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Editing collections

  Background:
    Given I am using the mobile site
      And I am logged into the mobile website
      And I am in alpha mode
      And I view one of my public collections

  Scenario: Edit button shown
    Then I see edit collection button

  Scenario: Clicking edit button
    When I click the edit collection button
    Then I see the collection editor overlay
