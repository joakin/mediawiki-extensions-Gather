Given(/^I am using the mobile site$/) do
  on(MainPage) do |page|
    page.goto
    # A domain is explicitly given to avoid a bug in earlier versions of Chrome
    page.browser.cookies.add 'mf_useformat', 'true', domain: URI.parse(page.page_url_value).host
    page.refresh
  end
end

Given(/^I am in alpha mode$/) do
  on(MainPage) do |page|
    page.goto
    # A domain is explicitly given to avoid a bug in earlier versions of Chrome
    page.browser.cookies.add 'optin', 'alpha', domain: URI.parse(page.page_url_value).host
    page.refresh
  end
end

Then(/^I see a toast$/) do
  expect(on(ArticlePage).toast_element.when_present).to be_visible
end

Given(/^I am on the "(.+)" page$/) do |article|
  # Ensure we do not cause a redirect
  article = article.sub(/ /, '_')
  visit(ArticlePage, using_params: { article_name: article })
end

When(/^I click the watchstar$/) do
  on(ArticlePage).watch_star_element.when_present.click
end
