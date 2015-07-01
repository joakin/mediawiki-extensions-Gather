@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Editing collections

  Background:
    Given I am using the mobile site
      And I am logged into the mobile website
      And I have Gather
      And I view one of my public collections

  Scenario: Edit button shown
    Then I see edit collection button

  Scenario: Clicking edit button
    When I click the edit collection button
    Then I see the collection editor overlay

  Scenario: Changing name
    When I click the edit collection button
        And I see the collection editor overlay
        And I wait
        And I click to edit name and description
        And I add " cool" to the name
        And I click to save settings
        And I wait
        And I click done
        And the page has reloaded
      Then the name of my collection contains " cool"
        And I see "_cool" in the page url

  Scenario: Changing description
    When I click the edit collection button
        And I see the collection editor overlay
        And I click to edit name and description
        And I enter "All work and no play makes Jack a dull boy" as the description
        And I click to save settings
        And I wait
        And I click done
        And the page has reloaded
    Then the description of my collection is "All work and no play makes Jack a dull boy"

  Scenario: Blank my description
    When I click the edit collection button
        And I see the collection editor overlay
        And I click to edit name and description
        And I enter "" as the description
        And I click to save settings
        And I wait
        And I click done
        And the page has reloaded
    Then the description of my collection is empty
