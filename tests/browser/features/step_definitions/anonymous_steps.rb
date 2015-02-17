Given(/^I am not logged in$/) do
end

When(/^I visit the Gather page$/) do
  visit(GatherPage)
end

Then(/^I see the login page$/) do
  expect(@browser.url).to match(/Special\:UserLogin/)
end

