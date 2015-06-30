Given(/^there are more than 100 collections$/) do
  response = api.action(:query, list: 'lists', lstmode: 'allpublic', lstlimit: 101)
  1.upto(101-response.data['lists'].length) { |i| make_collection("B#{i}") }
end

Given(/^I see 100 collections$/) do
  on(GatherRecentPage).collection_one_hundred_element.should exist
end

When(/^I scroll to the bottom of the page$/) do
  browser.execute_script("document.getElementById( 'footer' ).scrollIntoView();")
end

Then(/^I see more than 100 collections$/) do
  expect(on(GatherRecentPage).collection_one_hundred_plus_one_element.when_present(10)).to be_visible
end

Then(/^the 101st collection has a title$/) do
  on(GatherRecentPage).collection_one_hundred_plus_one_title_element.should exist
end

Then(/^the 101st collection has an owner$/) do
  on(GatherRecentPage).collection_one_hundred_plus_one_owner_element.should exist
end
