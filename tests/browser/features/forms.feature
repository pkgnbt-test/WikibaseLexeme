@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Forms of a Lexeme

  Background:
    Given I am on a Lexeme page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

@integration
  Scenario: Basic Forms section
    Then Forms header should be there
     And Forms container should be there
     And for each Form there is a representation and an ID
     And each representation is enclosed in tag having lang attribute with "some language" as a value