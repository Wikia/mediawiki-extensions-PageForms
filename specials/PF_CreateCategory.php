<?php
/**
 * A special page holding a form that allows the user to create a category
 * page, with PF forms associated with it
 *
 * @author Yaron Koren
 * @file
 * @ingroup PF
 */

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;

/**
 * @ingroup PFSpecialPages
 */
class PFCreateCategory extends SpecialPage {

	function __construct() {
		parent::__construct( 'CreateCategory', 'edit' );
	}

	static function createCategoryText( $default_form, $category_name, $parent_category ) {
		if ( $default_form === '' ) {
			$text = wfMessage( 'pf_category_desc', $category_name )->inContentLanguage()->text();
		} else {
			$text = "{{#default_form:$default_form}}";
		}
		if ( $parent_category !== '' ) {
			$namespace_labels = PFUtils::getContLang()->getNamespaces();
			$category_namespace = $namespace_labels[NS_CATEGORY];
			$text .= "\n\n[[$category_namespace:$parent_category]]";
		}
		return $text;
	}

	function execute( $query ) {
		$this->setHeaders();
		$this->checkPermissions();

		$out = $this->getOutput();
		$req = $this->getRequest();
		$out->enableOOUI();

		// Cycle through the query values, setting the appropriate
		// local variables.
		if ( $query !== null ) {
			$presetCategoryName = str_replace( '_', ' ', $query );
			$out->setPageTitle( $this->msg( 'pf-createcategory-with-name', $presetCategoryName )->text() );
			$category_name = $presetCategoryName;
		} else {
			$presetCategoryName = null;
			$category_name = $req->getVal( 'category_name' );
		}
		$default_form = $req->getVal( 'default_form', '' );
		$parent_category = $req->getVal( 'parent_category', '' );

		$save_page = $req->getCheck( 'wpSave' );
		$preview_page = $req->getCheck( 'wpPreview' );
		if ( $save_page || $preview_page ) {
			// Guard against cross-site request forgeries (CSRF).
			$validToken = $this->getUser()->matchEditToken( $req->getVal( 'csrf' ), 'CreateCategory' );
			if ( !$validToken ) {
				$text = "This appears to be a cross-site request forgery; canceling save.";
				$out->addHTML( $text );
				return;
			}
			// Redirect to wiki interface
			$out->setArticleBodyOnly( true );
			$title = Title::makeTitleSafe( NS_CATEGORY, $category_name );
			$full_text = self::createCategoryText( $default_form, $category_name, $parent_category );
			$text = PFUtils::printRedirectForm( $title, $full_text, "", $save_page, $this->getUser() );
			$out->addHTML( $text );
			return;
		}

		// Set 'title' as hidden field, in case there's no URL niceness.
		$text = "\t" . '<form action="" method="post">' . "\n";
		$firstRow = '';
		if ( $presetCategoryName === null ) {
			$text .= "\t" . Html::hidden( 'title', $this->getPageTitle()->getPrefixedText() ) . "\n";

			$categoryNameTextInput = new OOUI\TextInputWidget( [
				'required' => true,
				'name' => 'category_name'
			] );
			$firstRow .= new OOUI\FieldLayout(
				$categoryNameTextInput,
				[
					'label' => $this->msg( 'pf_createcategory_name' )->escaped(),
					'align' => 'top'
				]
			);
		}
		// Listing every form and category in dropdowns doesn't scale to
		// large wikis, so use title autocompletion (bounded prefix search).
		$secondRow = '';
		if ( PFUtils::namespaceHasPages( PF_NS_FORM ) ) {
			$formSelector = PFUtils::titleInputWidget( 'default_form', 'form_dropdown', PF_NS_FORM, $out );
			$secondRow .= new OOUI\FieldLayout(
				$formSelector,
				[
					'label' => $this->msg( 'pf_createcategory_defaultform' )->escaped(),
					'align' => 'top'
				]
			);
		}
		$text .= $firstRow . $secondRow . "\n";
		$selectBody = PFUtils::titleInputWidget( 'parent_category', 'category_dropdown', NS_CATEGORY, $out );
		$thirdRow = new OOUI\FieldLayout(
				$selectBody,
				[
					'label' => $this->msg( 'pf_createcategory_makesubcategory' )->escaped(),
					'align' => 'top'
				]
		);
		$text .= $thirdRow . "\n";

		$text .= "\t" . Html::hidden( 'csrf', $this->getUser()->getEditToken( 'CreateCategory' ) ) . "\n";
		$savePageButton = new OOUI\ButtonInputWidget( [
			'label' => $this->msg( 'savearticle' )->text(),
			'type' => 'submit',
			'name' => 'wpSave',
			'id' => 'wpSave',
			'flags' => [ 'primary', 'progressive' ],
			'useInputTag' => true
		] );

		$previewPageButton = new OOUI\ButtonInputWidget( [
			'label' => $this->msg( 'preview' )->text(),
			'type' => 'submit',
			'name' => 'wpPreview',
			'id' => 'wpPreview',
			'flags' => [ 'progressive' ],
			'useInputTag' => true
		] );

		$editButtonsText = $savePageButton . "\n";
		$editButtonsText .= $previewPageButton . "\n";
		$text .= "<br>" . Html::rawElement( 'div', [ 'class' => 'editButtons' ], $editButtonsText ) . "\n";
		$text .= "\t</form>\n";

		$out->addHTML( $text );
	}

	protected function getGroupName() {
		return 'pf_group';
	}
}
