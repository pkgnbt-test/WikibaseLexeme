<?php

namespace Wikibase\Lexeme\DummyObjects;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0-or-later
 */
class BlankForm extends Form {

	public function __construct() {
		parent::__construct(
			new NullFormId(),
			new TermList(),
			[]
		);
	}

	public function setId( $id ) {
		parent::setId( new DummyFormId( $id->getSerialization() ) );
	}

}
