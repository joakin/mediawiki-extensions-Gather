class GatherPage
  include PageObject
  include URL

  page_url URL.url('Special:Gather')
  a(:my_first_public_collection, css: '.collection-card-title a', index:1)
  a(:edit, css: '.edit-collection')
  div(:edit_overlay, css: '.collection-editor-overlay')
  div(:collection_items, css: '.collection-items')
end
