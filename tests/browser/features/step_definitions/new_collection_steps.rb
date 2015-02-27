When(/^I type "(.+)" into the new collection form$/) do |text|
  on(ArticlePage).collection_overlay_new_collection_input_element.when_present.send_keys(text)
end

When(/^I click the create collection button$/) do
  on(ArticlePage).collection_overlay_new_collection_button_element.when_present.click
end

Then(/^I see add to new collection button$/) do
  expect(on(ArticlePage).collection_overlay_new_collection_button_element.when_present).to exist
end

