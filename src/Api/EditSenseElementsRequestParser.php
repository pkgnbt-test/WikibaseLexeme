<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestParser {

	const PARAM_DATA = 'data';

	const PARAM_SENSE_ID = 'senseId';

	/**
	 * @var SenseIdDeserializer
	 */
	private $senseIdDeserializer;

	/**
	 * @var EditSenseChangeOpDeserializer
	 */
	private $editSenseChangeOpDeserializer;

	public function __construct(
		SenseIdDeserializer $senseIdDeserializer,
		EditSenseChangeOpDeserializer $editSenseChangeOpDeserializer
	) {
		$this->senseIdDeserializer = $senseIdDeserializer;
		$this->editSenseChangeOpDeserializer = $editSenseChangeOpDeserializer;
	}

	/**
	 * @param array $params
	 * @return EditSenseElementsRequest
	 */
	public function parse( array $params ) {
		// guarded against missing fields by EditSenseElements::getAllowedParams()

		//TODO: validate language. How?

		$dataValidation = ValidationContext::create( self::PARAM_DATA );

		$data = json_decode( $params[self::PARAM_DATA], true );
		if ( !is_array( $data ) || empty( $data ) ) {
			$dataValidation->addViolation(
				new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
			);
		}

		$senseId = $this->senseIdDeserializer->deserialize(
			$params[self::PARAM_SENSE_ID],
			ValidationContext::create( self::PARAM_SENSE_ID )
		);

		$this->editSenseChangeOpDeserializer->setContext(
			$dataValidation
		);

		return new EditSenseElementsRequest(
			$senseId,
			$this->editSenseChangeOpDeserializer->createEntityChangeOp( $data )
		);
	}

}