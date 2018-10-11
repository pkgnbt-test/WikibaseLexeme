<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpRemoveSense
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSenseTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveSense = new ChangeOpRemoveSense( new SenseId( 'L1-S1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveSense->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validateFailsIfProvidedEntityLacksSense() {
		$changeOpRemoveSense = new ChangeOpRemoveSense( new SenseId( 'L1-S1' ) );

		$result = $changeOpRemoveSense->validate( NewLexeme::create()->build() );

		$this->assertFalse( $result->isValid() );
	}

	public function test_validatePassesIfProvidedEntityIsLexemeAndHasSense() {
		$changeOpRemoveSense = new ChangeOpRemoveSense( new SenseId( 'L1-S1' ) );

		$result = $changeOpRemoveSense->validate(
			NewLexeme::create()
				->withSense( NewSense::havingId( new SenseId( 'L1-S1' ) )->build() )
				->build()
		);

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveSense = new ChangeOpRemoveSense( new SenseId( 'L1-S1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveSense->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyRemovesSenseIfGivenALexeme() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$sense = $lexeme->getSenses()->toArray()[0];

		$changeOp = new ChangeOpRemoveSense( $sense->getId() );
		$changeOp->apply( $lexeme );

		$this->assertCount( 0, $lexeme->getSenses() );
	}

	public function test_applySetsTheSummary() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'fr', 'goat' )
			)
			->build();
		$sense = $lexeme->getSenses()->toArray()[0];

		$changeOp = new ChangeOpRemoveSense( $sense->getId() );
		$summary = new Summary();
		$changeOp->apply( $lexeme, $summary );

		$this->assertCount( 0, $lexeme->getSenses() );

		$this->assertSame( 'remove-sense', $summary->getMessageKey() );
		$this->assertSame( 'fr', $summary->getLanguageCode() );
		$this->assertSame( [ 'goat' ], $summary->getAutoSummaryArgs() );
		$this->assertSame( [ $sense->getId()->getSerialization() ], $summary->getCommentArgs() );
	}

}
