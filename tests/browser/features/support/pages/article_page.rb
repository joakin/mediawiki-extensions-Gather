class ArticlePage
  include PageObject
  page_url "<%= URI.encode(params[:article_name]) %><%= params[:hash] %>"

  # UI elements
  a(:mainmenu_button, id: 'mw-mf-main-menu-button')
  # left nav
  nav(:navigation, css: 'nav')

  # UI elements
  li(:watch_star, css: '.collection-star-container')

  # toast
  div(:toast_panel, css: '.toast-panel')

  # cta
  div(:cta, css: '.drawer.visible')

  # collections
  div(:collections_overlay, css: '.collection-overlay')
  li(:collections_overlay_collection_one, css: '.collection-overlay ul li', index: 0)
  text_field(:collection_overlay_new_collection_input, css: '.collection-overlay input')
  button(:collection_overlay_new_collection_button, css: '.collection-overlay .create-collection')
  button(:collections_overlay_more, css: '.more-collections')

  # tutorial
  div(:gather_tutorial, css: '.tutorial')
end
