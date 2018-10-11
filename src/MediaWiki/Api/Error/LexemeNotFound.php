<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use Wikibase\Lexeme\Domain\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class LexemeNotFound implements ApiError {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;
	}

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'wikibaselexeme-api-error-lexeme-not-found',
			[ $parameterName, $this->lexemeId->serialize() ]
		);
		return new \ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => []
		] );
	}

}
