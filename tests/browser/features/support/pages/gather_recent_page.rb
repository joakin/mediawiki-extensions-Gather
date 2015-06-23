class GatherRecentPage
  include PageObject

  page_url 'Special:Gather/all/recent'
  a(:my_collections_button, css: '.button-bar a', index: 1)
  div(:collection_one_hundred, css: '.collection-card', index: 99 )
  div(:collection_one_hundred_plus_one, css: '.collection-card', index: 100 )
  div(:collection_one_hundred_plus_one_title, css: '.collection-card .collection-card-title', index: 100 )
  a(:collection_one_hundred_plus_one_owner, css: '.collection-card .collection-owner', index: 100 )
end
