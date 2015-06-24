class GatherPublicPage
  include PageObject

  page_url 'Special:GatherLists'
  a(:collection_link, css: '.gather-lists li a', index: 1)
end
