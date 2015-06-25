Given(/^I am using the mobile site$/) do
  on(MainPage) do |page|
    page.goto
    # A domain is explicitly given to avoid a bug in earlier versions of Chrome
    page.browser.cookies.add 'mf_useformat', 'true', domain: URI.parse(page.page_url_value).host
    page.refresh
  end
end

Given(/^I view one of my public collections$/) do
  visit(GatherPage)
  on(GatherPage).my_first_public_collection_element.click
end

Given(/^I am logged into the mobile website$/) do
  step 'I am using the mobile site'
  visit(LoginPage).login_with(ENV['MEDIAWIKI_USER'], ENV['MEDIAWIKI_PASSWORD'], false)
end

Then(/^I wait$/) do
  sleep 5
end

Given(/^I have Gather$/) do
  on(MainPage) do |page|
    page.goto
    # Disable the onboarding tutorials
    page.browser.execute_script("localStorage.setItem('gather-has-dismissed-tutorial','true');")
    # A domain is explicitly given to avoid a bug in earlier versions of Chrome
    page.browser.cookies.add 'optin', 'beta', domain: URI.parse(page.page_url_value).host
    page.refresh
  end
end

Then(/^I see a toast panel$/) do
  expect(on(ArticlePage).toast_panel_element.when_present).to be_visible
end

Given(/^I am on the "(.+)" page$/) do |article|
  # Ensure we do not cause a redirect
  article = article.sub(/ /, '_')
  visit(ArticlePage, using_params: { article_name: article })
end

When(/^I click the watchstar$/) do
  on(ArticlePage).watch_star_element.when_present.click
end
