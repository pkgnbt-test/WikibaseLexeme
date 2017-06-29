<?php

namespace Wikibase\Lexeme;

/**
 * Contains templates of lexemes commonly used in server-side output generation and client-side
 * JavaScript processing.
 *
 * @license GPL-2.0+
 *
 * @return array
 */

return call_user_func( function() {
	$templates = [];

	$templates['wikibase-lexeme-form'] = <<<'HTML'
<div class="wikibase-lexeme-form">
	<div class="wikibase-lexeme-form-header">
		<div class="wikibase-lexeme-form-id">$1</div>
		<div class="form-representations">$2</div>
	</div>
	$3
	$4
</div>
HTML;

	$templates['wikibase-lexeme-form-grammatical-features'] = <<<'HTML'
<div class="wikibase-lexeme-form-grammatical-features">
		<div class="wikibase-lexeme-form-grammatical-features-header">Grammatical features</div>
		<div class="wikibase-lexeme-form-grammatical-features-values">$1</div>
</div>
HTML;

	$templates['wikibase-lexeme-sense'] = <<< 'HTML'
<div class="wikibase-lexeme-sense" data-sense-id="$1">
    $2
    $3
</div>
HTML;

	return $templates;
} );
