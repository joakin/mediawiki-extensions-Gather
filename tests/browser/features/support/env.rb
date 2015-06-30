require 'mediawiki_selenium'
require 'mediawiki_selenium/support'
require 'mediawiki_selenium/step_definitions'

module Gather
  def make_collection(label)
    api.action('editlist', token_type: 'watch', label: label, titles: 'A|B|C|D', mode: 'update', perm: 'public')
  end
end
World(Gather)
