Then(/^I see edit collection button$/) do
  expect(on(GatherPage).edit_element.when_present).to be_visible
end

When(/^I click the edit collection button$/) do
  on(GatherPage).edit_element.click
end

Then(/^I see the collection editor overlay$/) do
  expect(on(GatherPage).edit_overlay_element.when_present).to be_visible
end

Then(/^I enter "(.*?)" as the description$/) do |keys|
  on(GatherPage).edit_overlay_description_element.when_present.send_keys(keys)
end

Then(/^I click done$/) do
  on(GatherPage).edit_overlay_done_element.when_present.click
end

Then(/^the page has reloaded$/) do
  sleep 2
end

Then(/^the description of my collection is "(.*?)"$/) do |text|
  expect(on(GatherPage).collection_description_element.when_present.text).to match text
end
