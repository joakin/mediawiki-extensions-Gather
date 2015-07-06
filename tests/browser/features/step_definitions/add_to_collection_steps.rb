Given(/^I have more than 100 collections$/) do
  response = api.action(:query, list: 'lists', owner: user, lstlimit: 101)
  1.upto(101-response.data['lists'].length) { |i| make_collection("B#{i}") }
end

When(/^I select a collection$/) do
  on(ArticlePage).collections_overlay_collection_one_element.when_present.click
end

Then(/^I see the collection dialog$/) do
  expect(on(ArticlePage).collections_overlay_element).to exist
end

Then(/^I see a more button$/) do
  expect(on(ArticlePage).collections_overlay_more_element.when_present).to exist
end
