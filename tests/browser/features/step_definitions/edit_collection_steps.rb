Then(/^I see edit collection button$/) do
  expect(on(GatherPage).edit_element.when_present).to be_visible
end

When(/^I click the edit collection button$/) do
  on(GatherPage).edit_element.click
end

Then(/^I see the collection editor overlay$/) do
  expect(on(GatherPage).edit_overlay_element.when_present).to be_visible
end

Then(/^I add "(.*?)" to the name$/) do |keys|
  on(GatherPage).edit_overlay_title_element.when_present.send_keys(keys)
end

Then(/^I enter "(.*?)" as the description$/) do |keys|
  on(GatherPage).edit_overlay_description_element.when_present.clear
  on(GatherPage).edit_overlay_description_element.when_present.send_keys(keys)
end

Then(/^I click done$/) do
  on(GatherPage).edit_overlay_done_element.when_present.click
end

Then(/^I click to save settings$/) do
  on(GatherPage).edit_overlay_save_desc_element.when_present.click
end

Then(/^the page has reloaded$/) do
  sleep 5
end

Then(/^I see "(.*?)" in the page url$/) do |text|
  on(GatherPage) do |page|
    expect(page.current_url).to match text
  end
end

Then(/^the name of my collection contains "(.*?)"$/) do |text|
  expect(on(GatherPage).collection_title_element.when_present.text).to match text
end

Then(/^the description of my collection is "(.*?)"$/) do |text|
  expect(on(GatherPage).collection_description_element.when_present.text).to eq text
end

Then(/^the description of my collection is empty$/) do
  expect(on(GatherPage).collection_description_element.when_not_present).to be_nil
end

When(/^I click to edit name and description$/) do
  on(GatherPage).edit_name_and_description_element.when_present.click
end

