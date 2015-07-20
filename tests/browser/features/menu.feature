@chrome @en.m.wikipedia.beta.wmflabs.org
Feature: Menu

Background:
  Given I am using the mobile site

Scenario: Check links in menu
  And I have Gather
  And I am on the "Main Page" page
  When I click on the main navigation button
  Then I should see a link to "Collections" in the main navigation menu

Scenario: Check links in menu
  And I have Gather
  And I am on the "Special:MobileOptions" page
  When I click on the main navigation button
  Then I should see a link to "Collections" in the main navigation menu

Scenario: Check links in menu for stable users
  And I am on the "Special:MobileOptions" page
  When I click on the main navigation button
  Then I should not see a link to "Collections" in the main navigation menu