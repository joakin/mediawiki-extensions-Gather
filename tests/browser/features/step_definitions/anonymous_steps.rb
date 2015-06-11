Given(/^I am not logged in$/) do
end

When(/^I visit the Gather page$/) do
  visit(GatherPage)
end

When(/^I visit a private collection page$/) do
  visit(WatchlistCollectionPage)
end

When(/^I click the my collections tab$/) do
  on(GatherRecentPage).my_collections_button_element.click
end


Then(/^I see the recent collections page$/) do
  expect(@browser.url).to match(/Special\:Gather\/all\/recent/)
end

Then(/^I see the login page$/) do
  expect(@browser.url).to match(/Special\:UserLogin/)
end

Then(/^I see the error page$/) do
  expect(on(NotFoundPage).title_element).to exist
end

Then(/^I see the anonymous CTA$/) do
  expect(on(ArticlePage).cta_element.when_present).to be_visible
end

When(/^I visit a public collection$/) do
  visit(GatherPublicPage)
  on(GatherPublicPage).collection_link_element.click
end

Then(/^I can see items in the collection$/) do
   expect(on(GatherPage).collection_items_element.when_present).to be_visible
end
