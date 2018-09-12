<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequest {

	private $senseId;
	private $changeOp;

	public function __construct( SenseId $senseId, ChangeOp $changeOp ) {
		$this->senseId = $senseId;
		$this->changeOp = $changeOp;
	}

	public function getChangeOp(): ChangeOp {
		return $this->changeOp;
	}

	public function getSenseId(): SenseId {
		return $this->senseId;
	}

}
