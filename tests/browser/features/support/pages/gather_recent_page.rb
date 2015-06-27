class GatherRecentPage
  include PageObject

  page_url 'Special:Gather/all/recent'
  a(:my_collections_button, css: '.button-bar a', index: 1)
end
