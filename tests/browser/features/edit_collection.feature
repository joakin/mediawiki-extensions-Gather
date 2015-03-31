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

  Scenario: Changing description
    When I click the edit collection button
    Then I see the collection editor overlay
        And I enter "All work and no play makes Jack a dull boy" as the description
        And I click done
        And the page has reloaded
        Then the description of my collection is "All work and no play makes Jack a dull boy"

  Scenario: Blank my description
    When I click the edit collection button
    Then I see the collection editor overlay
        And I enter "" as the description
        And I click done
        And the page has reloaded
        Then the description of my collection is ""
