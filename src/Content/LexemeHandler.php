<?php

namespace Wikibase\Lexeme\Content;

use IContextSource;
use Page;
use Title;
use UnexpectedValueException;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\EditEntityAction;
use Wikibase\HistoryEntityAction;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\Actions\ViewLexemeAction;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Store\EntityIdLookup;
use Wikibase\SubmitEntityAction;
use Wikibase\TermIndex;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeHandler extends EntityHandler {

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityLookup $entityLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory
	 * @param FieldDefinitions $lexemeFieldDefinitions
	 * @param callable|null $legacyExportFormatDetector
	 */
	public function __construct(
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelLookupFactory,
		FieldDefinitions $lexemeFieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		parent::__construct(
			LexemeContent::CONTENT_MODEL_ID,
			$termIndex,
			$contentCodec,
			$constraintProvider,
			$errorLocalizer,
			$entityIdParser,
			$lexemeFieldDefinitions,
			$legacyExportFormatDetector
		);

		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
		$this->labelLookupFactory = $labelLookupFactory;
	}

	/**
	 * @see ContentHandler::getActionOverrides
	 *
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'history' => function( Page $page, IContextSource $context ) {
				return new HistoryEntityAction(
					$page,
					$context,
					$this->entityIdLookup,
					$this->labelLookupFactory->newLabelDescriptionLookup( $context->getLanguage() )
				);
			},
			'view' => ViewLexemeAction::class,
			'edit' => EditEntityAction::class,
			'submit' => SubmitEntityAction::class,
		];
	}

	/**
	 * @return Lexeme
	 */
	public function makeEmptyEntity() {
		return new Lexeme();
	}

	public function makeEntityRedirectContent( EntityRedirect $redirect ) {
		$title = $this->getTitleForId( $redirect->getTargetId() );
		return LexemeContent::newFromRedirect( $redirect, $title );
	}

	/**
	 * @see EntityHandler::supportsRedirects()
	 */
	public function supportsRedirects() {
		return true;
	}

	/**
	 * @see EntityHandler::newEntityContent
	 *
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return LexemeContent
	 */
	protected function newEntityContent( EntityHolder $entityHolder = null ) {
		if ( $entityHolder !== null && $entityHolder->getEntityType() === Form::ENTITY_TYPE ) {
			$formId = $entityHolder->getEntityId();
			if ( !( $formId instanceof FormId ) ) {
				throw new UnexpectedValueException( '$formId must be a FormId' );
			}
			$lexemeId = $formId->getLexemeId();
			$entityHolder = new EntityInstanceHolder( $this->entityLookup->getEntity( $lexemeId ) );
		}
		if ( $entityHolder !== null && $entityHolder->getEntityType() === Sense::ENTITY_TYPE ) {
			$senseId = $entityHolder->getEntityId();
			if ( !( $senseId instanceof SenseId ) ) {
				throw new UnexpectedValueException( '$senseId must be a SenseId' );
			}
			$lexemeId = $senseId->getLexemeId();
			$entityHolder = new EntityInstanceHolder( $this->entityLookup->getEntity( $lexemeId ) );
		}
		return new LexemeContent( $entityHolder );
	}

	/**
	 * @param string $id
	 *
	 * @return LexemeId
	 */
	public function makeEntityId( $id ) {
		return new LexemeId( $id );
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return Lexeme::ENTITY_TYPE;
	}

	/**
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'NewLexeme';
	}

	public function getIdForTitle( Title $target ) {
		if ( $target->hasFragment() ) {
			$fragment = $target->getFragment();
			// TODO use an EntityIdParser (but parent's $this->entityIdParser is currently private)
			if ( preg_match( FormId::PATTERN, $fragment ) ) {
				return new FormId( $fragment );
			}
			if ( preg_match( SenseId::PATTERN, $fragment ) ) {
				return new SenseId( $fragment );
			}
		}

		return parent::getIdForTitle( $target );
	}

}
