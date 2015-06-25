Given(/^I have not used Gather before$/) do
  # enable the onboarding tutorial.
  on(MainPage) do |page|
    page.goto
    # Disable the onboarding tutorials
    page.browser.execute_script("localStorage.removeItem('gather-has-dismissed-tutorial');")
  end
end

Then(/^I see the onboarding tutorial$/) do
  expect(on(ArticlePage).gather_tutorial_element.when_present).to be_visible
end
