<?php

namespace Wikibase\Lexeme\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\DataModel\Form;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRepresentation implements ChangeOp {

	const SUMMARY_ACTION_ADD = 'add-form-representations';
	const SUMMARY_ACTION_SET = 'set-form-representations';

	/**
	 * @var Term
	 */
	private $representation;

	public function __construct( Term $representation ) {
		$this->representation = $representation;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Form::class, $entity, '$entity' );

		/** @var Form $entity */

		$this->updateSummary( $entity, $summary );

		$entity->getRepresentations()->setTerm( $this->representation );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

	private function updateSummary( Form $form, Summary $summary = null ) {
		if ( $summary === null ) {
			return;
		}

		// no op to summarize if term existed in identical fashion
		if ( $form->getRepresentations()->hasTerm( $this->representation ) ) {
			return;
		}

		$languageCode = $this->representation->getLanguageCode();
		$representation = $this->representation->getText();
		$summary->setAction(
			$form->getRepresentations()->hasTermForLanguage( $languageCode ) ?
			self::SUMMARY_ACTION_SET :
			self::SUMMARY_ACTION_ADD
		);
		$summary->setLanguage( $languageCode );
		$summary->addAutoCommentArgs( [
			$form->getId()->getSerialization() // TODO: use FormId not string?
		] );
		$summary->addAutoSummaryArgs( [ $languageCode => $representation ] );
	}

}
