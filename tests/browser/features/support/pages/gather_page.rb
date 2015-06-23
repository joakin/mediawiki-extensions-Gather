class GatherPage
  include PageObject
  include URL

  page_url URL.url('Special:Gather')
  a(:my_first_public_collection, css: '.collection-card-title a', index:1)
  a(:edit, css: '.edit-collection')
  div(:edit_overlay, css: '.collection-editor-overlay')
  textarea(:edit_overlay_description, css: '.collection-editor-overlay .description')
  button(:edit_overlay_save_desc,
    css: '.collection-editor-overlay .save-description')
  button(:edit_overlay_done,
    css: '.collection-editor-overlay .save')
  div(:collection_items, css: '.collection-cards')
  div(:collection_description, css: '.collection-description')
  button(:edit_name_and_description, css: '.settings-action')
end
