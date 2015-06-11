class GatherRecentPage
  include PageObject
  include URL

  page_url URL.url('Special:Gather/all/recent')
  a(:my_collections_button, css: '.button-bar a', index: 1)
end
