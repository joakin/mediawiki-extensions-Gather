@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Add to a collection for the first time

  Background:
    Given I am logged into the mobile website
      And I am using the mobile site
      And I have Gather
      And I have not used Gather before
      And I am on the "Selenium Gather test" page

  Scenario: The onboarding tutorial should show to new users
    When I click the watchstar
    Then I see the collection dialog
        And I see the onboarding tutorial
