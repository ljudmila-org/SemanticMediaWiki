<?php

namespace SMW\MediaWiki\Hooks;

use File;
use Hooks;
use MediaWiki\HookContainer\HookContainer;
use ParserOptions;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\HookListener;
use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use User;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class FileUpload implements HookListener {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var HookContainer
	 */
	private $hookContainer;

	public function __construct(
		NamespaceExaminer $namespaceExaminer,
		HookContainer $hookContainer
	) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @since 3.0
	 *
	 * @param File $file
	 * @param bool $reUploadStatus
	 *
	 * @return true
	 */
	public function process( File $file, $reUploadStatus = false ) {
		if ( $this->canProcess( $file->getTitle() ) ) {
			$this->doProcess( $file, $reUploadStatus );
		}

		return true;
	}

	private function canProcess( $title ) {
		return $title !== null && $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() );
	}

	private function doProcess( $file, $reUploadStatus = false ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$filePage = $this->makeFilePage( $file, $reUploadStatus );

		// Avoid WikiPage.php: The supplied ParserOptions are not safe to cache.
		// Fix the options or set $forceParse = true.
		$forceParse = true;

		$parserData = $applicationFactory->newParserData(
			$file->getTitle(),
			$filePage->getParserOutput( $this->makeCanonicalParserOptions(), null, $forceParse )
		);

		$pageInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$filePage
		);

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$semanticData = $parserData->getSemanticData();
		$semanticData->setOption( 'is_fileupload', true );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		// 2.4+
		$this->hookContainer->run( 'SMW::FileUpload::BeforeUpdate', [ $filePage, $semanticData ] );

		$parserData->setOrigin( 'FileUpload' );

		$parserData->pushSemanticDataToParserOutput();
		$parserData->updateStore( true );

		return true;
	}

	private function makeFilePage( $file, $reUploadStatus ) {
		$filePage = ApplicationFactory::getInstance()->newPageCreator()->createFilePage(
			$file->getTitle()
		);

		$filePage->setFile( $file );
		// TODO: Illegal dynamic property (#5421)
		$filePage->smwFileReUploadStatus = $reUploadStatus;

		return $filePage;
	}

	/**
	 * Anonymous user with default preferences and content language
	 */
	private function makeCanonicalParserOptions() {
		return ParserOptions::newFromUserAndLang(
			new User(),
			Localizer::getInstance()->getContentLanguage()
		);
	}

}
