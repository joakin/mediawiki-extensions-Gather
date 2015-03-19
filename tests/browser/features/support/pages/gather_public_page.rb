class GatherPublicPage
  include PageObject
  include URL

  page_url URL.url('Special:GatherLists')
  a(:collection_link, css: '.gather-lists li a', index: 1)
end
