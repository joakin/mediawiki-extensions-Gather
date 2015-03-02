class ArticlePage
  include PageObject

  include URL
  page_url URL.url('<%=params[:article_name]%><%=params[:hash]%>')
end

