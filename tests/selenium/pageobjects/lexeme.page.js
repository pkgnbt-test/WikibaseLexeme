'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	_ = require( 'lodash' ),
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' ),
	MainStatementSection = require( 'wdio-wikibase/pagesections/main.statement.section' ),
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );

class LexemePage extends MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction ) {

	static get LEMMA_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			SAVE_BUTTON: '.lemma-widget_save',
			ADD_BUTTON: '.lemma-widget_add',
			EDIT_INPUT_VALUE: '.lemma-widget_lemma-value-input',
			EDIT_INPUT_LANGUAGE: '.lemma-widget_lemma-language-input',
			EDIT_INPUT_LEXEME_LANGUAGE: '#lexeme-language',
			EDIT_INPUT_LEXEME_LEXICAL_CATEGORY: '#lexeme-lexical-category'
		};
	}

	static get FORM_WIDGET_SELECTORS() {
		return {
			REPRESENTATIONS: '.wikibase-lexeme-form-header .representation-widget .representation-widget_representation-list li',
			REPRESENTATION_VALUE: '.wikibase-lexeme-form-header .representation-widget_representation-value',
			REPRESENTATION_LANGUAGE: '.wikibase-lexeme-form-header .representation-widget_representation-language',
			EDIT_INPUT_VALUE: '.representation-widget_representation-value-input',
			EDIT_INPUT_LANGUAGE: '.representation-widget_representation-language-input',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			ADD_REPRESENTATION_BUTTON: '.representation-widget_add',
			ADD_STATEMENT_TO_FORM: '.wikibase-statementgrouplistview .wikibase-toolbar-button-add a',
			REMOVE_REPRESENTATION_BUTTON: '.representation-widget_representation-remove',
			FORM_STATEMENT_LIST: '.wikibase-lexeme-form-body .wikibase-statementgrouplistview .wikibase-listview'
		};
	}

	static get GENERIC_TOOLBAR_SELECTORS() {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save',
			CANCEL_BUTTON: '.wikibase-toolbar-button-cancel'
		};
	}

	static get PERSONAL_BAR() {
		return {
			USER_NOT_LOGIN_ICON: '#pt-anonuserpage',
			USER_TOOLBAR: '#p-personal'
		};
	}

	get lemmaContainer() {
		return $( '.lemma-widget_lemma-list' );
	}

	get lemmas() {
		return this.lemmaContainer.$$( '.lemma-widget_lemma-edit-box' );
	}

	get formsContainer() {
		return $( '.wikibase-lexeme-forms' );
	}

	get forms() {
		return this.formsContainer.$$( '.wikibase-lexeme-form' );
	}

	get formClaimValueInputField() {

		return $( '.wikibase-listview #new .wikibase-snakview-value-container .valueview-value .valueview-input' );

	}

	get viewHistoryLink() {
		return $( '#right-navigation #p-views #ca-history a' );
	}

	get restoreRevisionLink() {
		let element = browser.element( 'a[href*=restore]' ); // This link doesn't have any css identifier

		return element;
	}

	get undoRevisionLink() {
		return $( '#mw-content-text #pagehistory li .mw-history-undo a' );
	}

	get undoOrRestoreSavePageButton() {
		return $( '#bodyContent #mw-content-text .editOptions .editButtons button' ); // submit undo on the Undoing edit page
	}

	get addFormCancelLink() {
		return $( this.constructor.GENERIC_TOOLBAR_SELECTORS.CANCEL_BUTTON );
	}

	get headerId() {
		return $( '.wb-lexeme-header_id' ).getText().replace( /[^L0-9]/g, '' ); // remove non-marked-up styling text "(L123)"
	}

	get formId() {
		return $( '.wikibase-lexeme-form-header > .wikibase-lexeme-form-id' );
	}

	get hasFormHeader() {
		return $( '.wikibase-lexeme-forms-section h2 > #forms' ).isExisting();
	}

	get addFormLink() {
		return $( '.wikibase-lexeme-forms-section > .wikibase-addtoolbar .wikibase-toolbar-button-add a' );
	}

	get hasGramaticalFeatureList() {
		return $( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ).isExisting();
	}

	get hasRepresentation() {
		return $( '.wikibase-lexeme-form-header > .representation-widget' ).isExisting();
	}

	get formStatementList() {
		return $( this.constructor.FORM_WIDGET_SELECTORS.FORM_STATEMENT_LIST );
	}

	get headerSaveButton() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON );
	}

	get lexemeLanguageInput() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LANGUAGE );
	}

	get lexemeLexicalCategoryInput() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LEXICAL_CATEGORY );
	}

	/**
	 * Open the given Lexeme page
	 *
	 * @param {string} lexemeId
	 */
	open( lexemeId ) {
		const title = 'Lexeme:' + lexemeId;
		super.openTitle( title );
		try {
			browser.waitForVisible( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON );
		} catch ( e ) {
			// reload and try again once, in case the lexeme is new
			// and the first load hit a lagged replica (T232364)
			super.openTitle( title );
			browser.waitForVisible( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON );
		}
		this.addFormLink.waitForVisible(); // last button on page, probably the last
	}

	/**
	 * @param {string} lemmaText
	 * @param {string} languageCode
	 */
	setFirstLemma( lemmaText, languageCode ) {
		this.startHeaderEditMode();

		this.fillNthLemma( 0, lemmaText, languageCode );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( null, true );
	}

	startHeaderEditMode() {
		$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).click();
		browser.waitUntil( () => {
			return this.lexemeLanguageInput.getValue();
		} );
	}

	fillNthLemma( position, lemmaText, languageCode ) {
		for ( let i = this.lemmas.length; i <= position; i++ ) {
			$( this.constructor.LEMMA_WIDGET_SELECTORS.ADD_BUTTON ).click();
		}

		let lemma = this.lemmas[ position ];
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( lemmaText );
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( languageCode );
	}

	/**
	 * Set the language for the lexeme
	 *
	 * This will retry entering the id if it isn't found immeadietly, because cirrus and database replicas might be slow
	 * to update.
	 *
	 * @param {string} item Item ID to enter into the field
	 */
	setLexemeLanguageItem( item ) {
		this.lexemeLanguageInput.setValue( item );
		this.selectFirstSuggestedEntityOnEntitySelector();

		browser.waitUntil( () => {
			if ( this.waitTillHeaderIsSaveableOrError() ) {
				return true;
			}
			this._waitAndRetryInput( this.lexemeLanguageInput, item );
			return false;
		} );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( null, true );
	}

	/**
	 * Set the lexical category for the lexeme
	 *
	 * This will retry entering the id if it isn't found immeadietly, because cirrus and database replicas might be slow
	 * to update.
	 *
	 * @param {string} item Item ID to enter into the field
	 */
	setLexicalCategoryItem( item ) {
		this.lexemeLexicalCategoryInput.setValue( item );
		this.selectFirstSuggestedEntityOnEntitySelector();

		browser.waitUntil( () => {
			if ( this.waitTillHeaderIsSaveableOrError() ) {
				return true;
			}
			this._waitAndRetryInput( this.lexemeLexicalCategoryInput, item );
			return false;
		} );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( null, true );
	}

	/**
	 * Wait for a period of time and then enter a value into an input
	 *
	 * @param input the wdio input element into which to enter the value
	 * @param value value to be entered into the element
	 * @param {int} [timeoutMS] duration to wait in ms, default 1000 ms
	 * @private
	 */
	_waitAndRetryInput( input, value, timeoutMS ) {
		browser.call( () =>
			new Promise( ( resolve ) => {
				setTimeout( resolve, timeoutMS || 1000 );
			} ).then( () => {
				input.setValue( value );
			} )
		);
	}

	/**
	 * Wait until the Lexeme header is saveable or there is an error message
	 *
	 * @returns {boolean} true if saveable, false if there is an error message
	 * @private
	 */
	waitTillHeaderIsSaveableOrError() {
		let isSaveable = false;
		browser.waitUntil( () => {
			if ( this.isHeaderSubmittable() ) {
				isSaveable = true;
				return true;
			}
			const errorMsg = $( 'body > ul.ui-entityselector-list > .ui-entityselector-notfound' );
			return errorMsg.isVisible();
		} );
		return isSaveable;
	}

	isHeaderSubmittable() {
		return this.headerSaveButton.getAttribute( 'disabled' ) !== 'true';
	}

	/**
	 * Add a form
	 *
	 * @param {string} value
	 * @param {string} language
	 */
	addForm( value, language ) {
		this.addFormLink.click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		this.formsContainer.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON ).click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForExist( null, true );
	}

	/**
	 * Remove the nth for on the page
	 *
	 * @param {int} index
	 */
	removeNthForm( index ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();

		let removeButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.REMOVE_BUTTON );

		removeButton.waitForVisible();
		removeButton.click();
		removeButton.waitForExist( null, true );
	}

	/**
	 * Get data of the nth form on the page
	 * Gets only the first form representation value
	 *
	 * @param {int} index
	 * @return {value, language}
	 */
	getNthFormData( index ) {
		let form = this.forms[ index ];

		return {
			value: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_VALUE ).getText(),
			language: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE ).getText(),
			grammaticalFeatures: form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ).getText()
		};
	}

	/**
	 * Get data of the nth form on the page
	 *
	 * @param {int} index
	 * @return {{value, language}}
	 */
	getNthFormFormValues( index ) {
		let form = this.forms[ index ],
			languageFields = form.$$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ),
			representationInputs = [];

		_.each( form.$$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ), function ( element, key ) {
			representationInputs.push( {
				value: element.getValue(),
				language: languageFields[ key ].getValue()
			} );
		} );

		return {
			representations: representationInputs
		};
	}

	getNthFormFormValuesAfterSave( index ) {
		let form = this.forms[ index ],
			languageFields = form.$$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATIONS ),
			representationValues = [];

		_.each( languageFields, function ( element, key ) {
			representationValues.push( {
				value: languageFields[ key ].$( '.representation-widget_representation-value' ).getText(),
				language: languageFields[ key ].$( '.representation-widget_representation-language' ).getText()
			} );
		} );

		return {
			representations: representationValues
		};
	}

	getNthFormStatement( index ) {
		let form = this.forms[ index ];

		form.$( '.wikibase-snakview-body .wikibase-snakview-variation-valuesnak  .valueview-instaticmode' ).waitForVisible();
		form.$( '.wikibase-statementgroupview-property-label a' ).waitForVisible();

		let property = form.$( '.wikibase-statementgroupview' ),
			value = form.$( '.wikibase-snakview-body .wikibase-snakview-variation-valuesnak  .valueview-instaticmode' );

		return {
			propertyId: property.getAttribute( 'id' ).split( '-' ), value: value.getText()
		};
	}

	addStatementToNthForm( index, statementPropertyId, statementValue, submitImmediately ) {
		let form = this.forms[ index ],
			addStatementLink = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_STATEMENT_TO_FORM );

		addStatementLink.click();

		let propertyInputfield = form.$( '.wikibase-statementgroupview .wikibase-snakview-property input' );

		propertyInputfield.setValue( statementPropertyId );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.formClaimValueInputField.waitForVisible();
		this.formClaimValueInputField.setValue( statementValue );

		if ( submitImmediately !== false ) {
			this.submitNthFormStatement( index );
		}
	}

	submitNthFormStatement( index ) {
		let form = this.forms[ index ],
			saveLink = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		browser.waitUntil( () => {
			return saveLink.getAttribute( 'aria-disabled' ) === 'false';
		} );

		saveLink.click();
		saveLink.waitForExist( null, true );
	}

	addRepresentationToNthForm( index, representation, language, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		let addRepresentationButton = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_REPRESENTATION_BUTTON );

		addRepresentationButton.waitForVisible();
		addRepresentationButton.click();

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let newRepresentationIndex = representations.length - 1;
		let newRepresentation = representations[ newRepresentationIndex ];

		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	restorePreviousRevision() {
		this.viewHistoryLink.click();
		this.restoreRevisionLink.click();
		this.undoOrRestoreSavePageButton.click();
		this.addFormLink.waitForVisible();
	}

	undoLatestRevision() {
		this.viewHistoryLink.click();
		this.undoRevisionLink.click();
		this.undoOrRestoreSavePageButton.click();
		this.addFormLink.waitForVisible();
	}

	editRepresentationOfNthForm( index, representation, language, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	removeLastRepresentationOfNthForm( index, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let lastRepresentationIndex = representations.length - 1;
		let lastRepresentation = representations[ lastRepresentationIndex ];

		lastRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.REMOVE_REPRESENTATION_BUTTON ).click();

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	startEditingNthForm( index ) {
		this.forms[ index ].$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();
	}

	isNthFormSubmittable( index ) {
		let form = this.forms[ index ],
			saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		return saveButton.getAttribute( 'aria-disabled' ) !== 'true';
	}

	submitNthForm( index ) {
		let form = this.forms[ index ];

		let saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	addGrammaticalFeatureToNthForm( index, grammaticalFeatureId, submitImmediately ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();

		this.setSingleValueOnMultiselectElement(
			form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ),
			grammaticalFeatureId
		);

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
		form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.GRAMMATICAL_FEATURE_ELENENT ).click();

	}

	removeGrammaticalFeatureFromNthForm( index, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );
		let gramFeaturesValues = form.$( '.wikibase-lexeme-form-grammatical-features-values' );
		let gramFeatureToDelete = gramFeaturesValues.$$( '.oo-ui-tagItemWidget' );
		let gramFeatureRemove = gramFeatureToDelete[ 0 ].$( '.oo-ui-buttonElement-button' );

		gramFeatureRemove.click();

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	getFormAnchor( index ) {
		let form = this.forms[ index ];

		return form.getAttribute( 'id' );
	}

	isUserLoggedIn() {
		$( this.constructor.PERSONAL_BAR.USER_TOOLBAR ).waitForExist( null, false );
		return !$( this.constructor.PERSONAL_BAR.USER_NOT_LOGIN_ICON ).isExisting();
	}

}

module.exports = new LexemePage();
