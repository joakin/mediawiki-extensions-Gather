Given(/^I am using the mobile site$/) do
  on(MainPage) do |page|
    page.goto
    # A domain is explicitly given to avoid a bug in earlier versions of Chrome
    page.browser.cookies.add 'mf_useformat', 'true', domain: URI.parse(page.page_url_value).host
    page.refresh
  end
end
