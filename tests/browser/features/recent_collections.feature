@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Viewing recent changes

  Background:
    Given I am using the mobile site
      And there are more than 100 collections

  @smoke
  Scenario: Infinite scrolling is working for anonymous users
    Given I am on the "Special:Gather/all/recent" page
      And I see 100 collections
    When I scroll to the bottom of the page
    Then I see more than 100 collections
      And the 101st collection has a title
      And the 101st collection has an owner

  Scenario: Infinite scrolling is working for logged in users
    Given I am logged into the mobile website
      And I am on the "Special:Gather/all/recent" page
      And I see 100 collections
    When I scroll to the bottom of the page
    Then I see more than 100 collections
