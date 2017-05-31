
class Statement
  include PageObject

  div(:property_label, class: 'wikibase-statementgroupview-property-label')
  div(:value, css: '.wikibase-statementview-mainsnak .wikibase-snakview-value')
  a(:save, css: '.wikibase-toolbar-button-save a' )
end

class StatementGroup
  include PageObject

  page_sections(:statements, Statement, css: '.wikibase-statementgroupview.listview-item')
  a(:add_statement, css: '.wikibase-statementgrouplistview > .wikibase-addtoolbar-container a')

  def statement_with_value?(property_label, value)
    self.statements.any? do |statement|
      statement.property_label_element.text == property_label && statement.value_element.text == value
    end
  end
end

class GrammaticalFeatureValue
  include PageObject

  a(:value)
end

class LexemeForm
  include PageObject

  span(:representation, class: 'wikibase-lexeme-form-text')
  div(:grammatical_feature_list, class: 'wikibase-lexeme-form-grammatical-features')
  div(:statements, class: 'wikibase-statementgrouplistview')
  textarea(:representation_input, css: '.wikibase-lexeme-form-text > textarea')
  text_field(:grammatical_features_input, css: '.wikibase-lexeme-form-grammatical-features-values input')
  a(:save, css: '.wikibase-toolbar-button-save > a')
  a(:cancel, css: '.wikibase-toolbar-button-cancel > a')
  a(:edit, css: '.wikibase-toolbar-button-edit > a')
  a(:grammatical_feature_selection_first_option, css: '.wikibase-lexeme-form-grammatical-features-values .oo-ui-menuOptionWidget:first-of-type a')

  page_section(:statement_group, StatementGroup, class: 'wikibase-statementgrouplistview')
  page_sections(:grammatical_features, GrammaticalFeatureValue, css: '.wikibase-lexeme-form-grammatical-features-values > span')

  def grammatical_feature?(label)
    self.grammatical_features.any? do |gf|
      gf.value_element.when_present.text == label
    end
  end
end


class Sense
  include PageObject

  h3(:sense_gloss, class: 'wikibase-lexeme-sense-gloss')
  span(:sense_id, class: 'wikibase-lexeme-sense-id')
end


class LexemePage
  include PageObject
  include EntityPage

  span(:forms_header, id: 'forms')
  div(:forms_container, class: 'wikibase-lexeme-forms')
  h3(:form_representation, class: 'wikibase-lexeme-form-representation')
  span(:form_id, class: 'wikibase-lexeme-form-id')
  span(:senses_header, id: 'senses')
  div(:senses_container, class: 'wikibase-lexeme-senses')

  page_sections(:forms, LexemeForm, class: 'wikibase-lexeme-form')
  page_sections(:senses, Sense, class: 'wikibase-lexeme-sense')

  # Lexeme Form
  a(:add_lexeme_form, css: '.wikibase-lexeme-forms-section > .wikibase-addtoolbar-container a')

  def create_lexeme(lexeme_data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_entity(lexeme_data, "lexeme")

    id = resp['entity']['id']
    url = URL.repo_url(ENV['LEXEME_NAMESPACE'] + id)
    { 'url' => url }
  end
end
