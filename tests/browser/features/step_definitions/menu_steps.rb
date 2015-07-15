When(/^I click on the main navigation button$/) do
  on(ArticlePage).mainmenu_button_element.when_present.click
end

Then(/^I should see a link to "(.*?)" in the main navigation menu$/) do |text|
  expect(on(ArticlePage).navigation_element.link_element(text: text)).to be_visible
end

Then(/^I should not see a link to "(.*?)" in the main navigation menu$/) do |text|
  expect(on(ArticlePage).navigation_element.link_element(text: text)).not_to be_visible
end
