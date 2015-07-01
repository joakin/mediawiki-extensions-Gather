class GatherPage
  include PageObject

  page_url 'Special:Gather'
  a(:my_first_public_collection, css: '.collection-card-title a', index:1)
  a(:edit, css: '.edit-collection')
  div(:edit_overlay, css: '.collection-editor-overlay')
  text_field(:edit_overlay_title, css: '.collection-editor-overlay .editor-pane .title')
  textarea(:edit_overlay_description, css: '.collection-editor-overlay .description')
  button(:edit_overlay_save_desc,
    css: '.collection-editor-overlay .save-description')
  button(:edit_overlay_done,
    css: '.collection-editor-overlay .overlay-action .save')
  div(:collection_items, css: '.collection-cards')
  h1(:collection_title, css: '.collection-header h1')
  div(:collection_description, css: '.collection-description')
  button(:edit_name_and_description, css: '.settings-action')
end
