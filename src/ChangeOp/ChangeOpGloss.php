<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpGloss implements ChangeOp {

	const SUMMARY_ACTION_ADD = 'add-sense-glosses';
	const SUMMARY_ACTION_SET = 'set-sense-glosses';

	/**
	 * @var Term
	 */
	private $gloss;

	public function __construct( Term $gloss ) {
		$this->gloss = $gloss;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Sense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Sense::class, $entity, '$entity' );

		/** @var Sense $entity */

		$this->updateSummary( $entity, $summary );

		$entity->getGlosses()->setTerm( $this->gloss );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

	private function updateSummary( Sense $sense, Summary $summary = null ) {
		if ( $summary === null ) {
			return;
		}

		// no op to summarize if term existed in identical fashion
		if ( $sense->getGlosses()->hasTerm( $this->gloss ) ) {
			return;
		}

		$languageCode = $this->gloss->getLanguageCode();
		$glossText = $this->gloss->getText();
		$summary->setAction(
			$sense->getGlosses()->hasTermForLanguage( $languageCode ) ?
			self::SUMMARY_ACTION_SET :
			self::SUMMARY_ACTION_ADD
		);
		$summary->setLanguage( $languageCode );
		$summary->addAutoCommentArgs( [
			$sense->getId()->getSerialization() // TODO: use SenseId not string?
		] );
		$summary->addAutoSummaryArgs( [ $languageCode => $glossText ] );
	}

}