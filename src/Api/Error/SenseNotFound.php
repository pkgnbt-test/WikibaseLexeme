<?php

namespace Wikibase\Lexeme\Api\Error;

use ApiMessage;
use Message;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseNotFound implements ApiError {

	/**
	 * @var SenseId
	 */
	private $senseId;

	public function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	/**
	 * @return ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new Message(
			'wikibaselexeme-api-error-sense-not-found',
			[ $parameterName, $this->senseId->serialize() ]
		);
		return new ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => []
		] );
	}

}
