<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\EntityContent;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Storage\GetLexemeException;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiLexemeRepository implements LexemeRepository {

	private $user;
	private $entityStore;
	private $userIsBot;
	private $entityRevisionLookup;

	/**
	 * @param \User $user
	 * @param bool $userIsBot
	 * @param EntityStore $entityStore Needs to be able to save Lexeme entities
	 * @param EntityRevisionLookup $entityRevisionLookup Needs to be able to retrieve Lexeme entities
	 */
	public function __construct( \User $user, $userIsBot, EntityStore $entityStore,
		EntityRevisionLookup $entityRevisionLookup ) {

		$this->user = $user;
		$this->userIsBot = $userIsBot;
		$this->entityStore = $entityStore;
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	public function updateLexeme( Lexeme $lexeme, /* string */ $editSummary ) {
		// TODO: assert id not null

		try {
			return $this->entityStore->saveEntity(
				$lexeme,
				$editSummary,
				$this->user,
				$this->getSaveFlags()
			);
		} catch ( StorageException $ex ) {
			throw new UpdateLexemeException( $ex );
		}
	}

	private function getSaveFlags() {
		// TODO: the EntityContent::EDIT_IGNORE_CONSTRAINTS flag does not seem to be used by Lexeme
		// (LexemeHandler has no onSaveValidators)
		$flags = EDIT_UPDATE | EntityContent::EDIT_IGNORE_CONSTRAINTS;

		if ( $this->userIsBot && $this->user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		return $flags;
	}

	/**
	 * @param LexemeId $id
	 *
	 * @return Lexeme|null
	 * @throws GetLexemeException
	 */
	public function getLexemeById( LexemeId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$id,
				0,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( $revision ) {
				return $revision->getEntity();
			}

			return null;
		} catch ( StorageException $ex ) {
			throw new GetLexemeException( $ex );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new GetLexemeException( $ex );
		}
	}

}
