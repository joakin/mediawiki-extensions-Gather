class GatherUserCollectionPage < GatherPage
  include PageObject

  page_url 'Special:Gather/id/<%= params[:id] %>'
end

