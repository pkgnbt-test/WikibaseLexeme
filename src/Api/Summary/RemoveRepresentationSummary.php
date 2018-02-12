<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class RemoveRepresentationSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string
	 */
	private $removedRepresentations;

	/**
	 * @param FormId $formId
	 * @param string[] $removedRepresentations
	 */
	public function __construct( FormId $formId, array $removedRepresentations ) {
		$this->formId = $formId;
		$this->removedRepresentations = $removedRepresentations;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		/** @see "wikibaselexeme-summary-remove-form-representations" message */
		return 'remove-form-representations';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->removedRepresentations;
	}

}
