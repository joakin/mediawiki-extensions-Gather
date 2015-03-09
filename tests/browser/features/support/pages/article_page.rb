class ArticlePage
  include PageObject

  include URL
  page_url URL.url('<%=params[:article_name]%><%=params[:hash]%>')

  # UI elements
  li(:watch_star, id: 'ca-watch')

  # toast
  div(:toast, class: 'toast')

  # cta
  div(:cta, css: '.drawer.visible')

  # collections
  div(:collections_overlay, css: '.collection-overlay')
  li(:collections_overlay_collection_one, css: '.collection-overlay ul li', index: 0)
  text_field(:collection_overlay_new_collection_input, css: '.collection-overlay input')
  button(:collection_overlay_new_collection_button, css: '.collection-overlay .mw-ui-constructive')
end
