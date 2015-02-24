Given(/^I am not logged in$/) do
end

When(/^I visit the Gather page$/) do
  visit(GatherPage)
end

When(/^I visit a private collection page$/) do
  visit(WatchlistCollectionPage)
end

Then(/^I see the login page$/) do
  expect(@browser.url).to match(/Special\:UserLogin/)
end

Then(/^I see the error page$/) do
  expect(on(NotFoundPage).title_element).to exist
end

