<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Merge\LexemeMerger;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Merge\LexemeMerger
 *
 * @license GPL-2.0-or-later
 */
class LexemeMergerTest extends TestCase {

	/**
	 * @var LexemeMerger
	 */
	private $lexemeMerger;

	public function setUp() {
		parent::setUp();

		$this->lexemeMerger = $this->newLexemeMerger();
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException
	 */
	public function testLexemesReferenceTheSameObjectCausesException() {
		$lexeme = $this->newMinimumValidLexeme( 'L36' )
			->build();

		$this->lexemeMerger->merge( $lexeme, $lexeme );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\ReferenceSameLexemeException
	 */
	public function testLexemesAreTheSameCausesException() {
		$source = $this->newMinimumValidLexeme( 'L37' )
			->build();
		$target = $source->copy();

		$this->lexemeMerger->merge( $source, $target );
	}

	public function testLemmasThatExistBothOnTheTargetAndTheSourceAreKeptOnTheTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withLemma( 'en', 'color' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withLemma( 'en', 'color' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertSame( 'color', $target->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testLemmasThatExistOnlyOnTheSourceAreAddedToTheTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withLemma( 'en', 'color' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withLemma( 'en-gb', 'colour' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertSame( 'color', $target->getLemmas()->getByLanguage( 'en' )->getText() );
		$this->assertSame( 'colour', $target->getLemmas()->getByLanguage( 'en-gb' )->getText() );
	}

	/**
	 * @dataProvider provideConflictingLemmas
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\ConflictingLemmaValueException
	 */
	public function testLexemesHaveLemmasWithSameLanguageButDifferentValueCausesException(
		array $sourceLemmas,
		array $targetLemmas
	) {
		$source = $this->newMinimumValidLexeme( 'L1' );
		foreach ( $sourceLemmas as $lemma ) {
			$source = $source->withLemma( $lemma[0], $lemma[1] );
		}
		$source = $source->build();
		$target = $this->newMinimumValidLexeme( 'L2' );
		foreach ( $targetLemmas as $lemma ) {
			$target = $target->withLemma( $lemma[0], $lemma[1] );
		}
		$target = $target->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	public function provideConflictingLemmas() {
		yield [ [ [ 'en', 'bar' ] ], [ [ 'en', 'foo' ] ] ];
		yield [ [ [ 'en', 'bar' ], [ 'en-gb', 'foo' ] ], [ [ 'en-gb', 'foo2' ] ] ];
		yield [ [ [ 'en', 'bar' ] ], [ [ 'en-gb', 'foo2' ], [ 'en', 'baz' ] ] ];
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\DifferentLanguagesException
	 */
	public function testLexemesHaveDifferentLanguageCausesException() {
		$source = NewLexeme::havingId( 'L1' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLanguage( 'Q8' )
			->withLexicalCategory( 'Q55' )
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\DifferentLexicalCategoriesException
	 */
	public function testLexemesHaveDifferentLexicalCategoriesCausesException() {
		$source = NewLexeme::havingId( 'L1' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q56' )
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceLexemeWithStatementItIsAddedToTarget() {
		$statement = NewStatement::noValueFor( 'P56' )
			->withGuid( 'L1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$source->getStatements()->addStatement( $statement );
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 1, $target->getStatements() );
		$this->assertSame(
			'P56',
			$target->getStatements()->getMainSnaks()[0]->getPropertyId()->serialize()
		);
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException
	 */
	public function testGivenSourceLexemeWithStatementReferencingTargetLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$source->getStatements()->addStatement( $statement );
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceWithMultipleRedundantFormsTheyAreIndividuallyAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->withForm( NewForm::havingId( 'F2' )->andRepresentation( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colours',
			$f1Representations->getByLanguage( 'en-gb' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
		$f3Representations = $target->getForms()
			->getById( new FormId( 'L2-F3' ) )
			->getRepresentations();
		$this->assertCount( 1, $f3Representations );
		$this->assertSame(
			'colors',
			$f3Representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenTargetWithMultipleMatchingFormsAllTheseFormsRemain() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->withForm( NewForm::havingId( 'F2' )->andRepresentation( 'en', 'colors' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colors',
			$f1Representations->getByLanguage( 'en' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
		$f3Representations = $target->getForms()
			->getById( new FormId( 'L2-F3' ) )
			->getRepresentations();
		$this->assertCount( 1, $f3Representations );
		$this->assertSame(
			'colours',
			$f3Representations->getByLanguage( 'en-gb' )->getText()
		);
	}

	public function testGivenLexemesWithMatchingFormsFormRepresentationsAreMerged() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
					->andRepresentation( 'en-gb', 'colours' )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$forms = $target->getForms();
		$this->assertCount( 1, $forms );
		$representations = $forms->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 2, $representations );
		$this->assertSame(
			'colours',
			$representations->getByLanguage( 'en-gb' )->getText()
		);
		$this->assertSame(
			'colors',
			$representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenLexemesWithNonMatchingFormsSourceFormsAreAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 2, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colours',
			$f1Representations->getByLanguage( 'en-gb' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenLexemesWithMultipleMatchingFormsFirstMatchMergedRestCopiedUnchanged() {
		$statement = NewStatement::noValueFor( 'P42' )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
				->andStatement( $statement )
			)->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
			)
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount(
			1,
			$target->getForms()->getById( new FormId( 'L2-F1' ) )->getStatements()
		);
		$this->assertCount(
			0,
			$target->getForms()->getById( new FormId( 'L2-F2' ) )->getStatements()
		);
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException
	 */
	public function testGivenSourceLexemeWithFormStatementReferencingTargetLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException
	 */
	public function testGivenTargetLexemeWithFormStatementReferencingSourceLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L1' ) )
			->withGuid( 'L2-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException
	 */
	public function testGivenSourceLexemeWithFormStatementReferencingTargetsFormExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new FormId( 'L2-F1' ) )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @expectedException \Wikibase\Lexeme\Merge\Exceptions\CrossReferencingException
	 */
	public function testGivenTargetLexemeWithFormStatementReferencingSourcesFormExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new FormId( 'L1-F1' ) )
			->withGuid( 'L2-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
					->andStatement( $statement )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @dataProvider provideFormMatchingSamples
	 */
	public function testFormMatchesAreDetected(
		$matchingOrNot,
		NewForm $sourceForm,
		NewForm $targetForm
	) {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( $sourceForm )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( $targetForm )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( $matchingOrNot ? 1 : 2, $target->getForms() );
	}

	public function provideFormMatchingSamples() {
		yield 'identical representations cause match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'identical representations and underspecified grammatical features cause no match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
				->andGrammaticalFeature( 'Q1' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'different representations prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'one identical and no contradicting representation causes match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'contradicting representations prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' )
		];
		yield 'identical representations and grammatical features cause match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
		];
		yield 'different grammatical features prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
				->andGrammaticalFeature( 'Q3' )
		];
		yield 'order of parts in identifier is irrelevant' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andRepresentation( 'en-gb', 'colour' )
				->andGrammaticalFeature( 'Q2' )
				->andGrammaticalFeature( 'Q1' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colour' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
		];
	}

	public function testGivenSourceLexemeWithFormWithStatementItIsAddedToMatchingTargetForm() {
		$statement = NewStatement::noValueFor( 'P56' )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'color' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )->andRepresentation( 'en', 'color' )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 1, $target->getForms() );
		$f1Statements = $target->getForms()->getById( new FormId( 'L2-F1' ) )->getStatements();
		$this->assertCount( 1, $f1Statements );
		foreach ( $f1Statements as $f1Statement ) {
			$this->assertStringStartsWith( 'L2-F1$', $f1Statement->getGuid() );
			$this->assertSame( 'P56', $f1Statement->getPropertyId()->serialize() );
		}
	}

	/**
	 * senses
	 * TODO https://phabricator.wikimedia.org/T199896
	 */

	/**
	 * @param string $id Lexeme id
	 * @return NewLexeme
	 */
	private function newMinimumValidLexeme( $id ) : NewLexeme {
		return NewLexeme::havingId( $id )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' );
	}

	private function newLexemeMerger() : LexemeMerger {
		$statementsMerger = WikibaseRepo::getDefaultInstance()
			->getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();

		return new LexemeMerger( $statementsMerger );
	}

}