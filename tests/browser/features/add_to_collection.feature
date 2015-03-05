@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Add to a collection

  Background:
    Given I am logged into the mobile website
      And I am using the mobile site
      And I am in alpha mode
      And I am on the "Selenium Gather test" page

  Scenario:
    When I click the watchstar
    Then I see the collection dialog

  Scenario:
    When I click the watchstar
     And I select a collection
    Then I see a toast
