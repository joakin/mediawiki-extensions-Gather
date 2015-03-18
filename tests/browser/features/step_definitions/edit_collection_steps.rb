Then(/^I see edit collection button$/) do
  expect(on(GatherPage).edit_element.when_present).to be_visible
end

When(/^I click the edit collection button$/) do
  on(GatherPage).edit_element.click
end

Then(/^I see the collection editor overlay$/) do
  expect(on(GatherPage).edit_overlay_element.when_present).to be_visible
end
