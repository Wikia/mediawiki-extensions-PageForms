<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;

/**
 * @group Database
 * @covers PFCreateCategory
 * @covers PFHelperFormAction
 * @covers PFUtils::normalizeTitleInput
 */
class PFCreateCategoryTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new PFCreateCategory();
	}

	private function submit( array $values ): string {
		$user = $this->getTestUser()->getUser();
		$values['csrf'] = $user->getEditToken( 'CreateCategory' );
		[ $html ] = $this->executeSpecialPage( null, new FauxRequest( $values, true ), null, $user );
		return $html;
	}

	public function testDeniesUsersWithoutEditRight(): void {
		$this->setGroupPermissions( '*', 'edit', false );

		$this->expectException( PermissionsError::class );
		$this->executeSpecialPage( '', null, null, new User() );
	}

	public function testDoesNotListExistingCategoriesOrForms(): void {
		$this->editPage( 'PFCreateCategoryTest member', '[[Category:PFCreateCategoryTest existing]]' );
		$this->editPage( Title::makeTitle( PF_NS_FORM, 'PFCreateCategoryTest form' ), 'form' );

		[ $html ] = $this->executeSpecialPage( '', null, null, $this->getTestUser()->getUser() );

		$this->assertStringContainsString( 'mw-widget-titleInputWidget', $html );
		$this->assertStringNotContainsString( 'PFCreateCategoryTest existing', $html );
		$this->assertStringNotContainsString( 'PFCreateCategoryTest form', $html );
	}

	public function testShowsNameInputWithoutPresetName(): void {
		[ $html ] = $this->executeSpecialPage( null, null, null, $this->getTestUser()->getUser() );

		$this->assertStringContainsString( 'name="category_name"', $html );
		$this->assertStringContainsString( 'name="parent_category"', $html );
	}

	public function testStripsNamespacePrefixesFromInputs(): void {
		$html = $this->submit( [
			'category_name' => 'PFCreateCategoryTest new',
			'default_form' => 'Form:Bar',
			'parent_category' => 'Category:Foo',
			'wpPreview' => '1',
		] );

		$this->assertStringContainsString( '{{#default_form:Bar}}', $html );
		$this->assertStringContainsString( '[[Category:Foo]]', $html );
	}

	public function testRejectsInputsFromOtherNamespaces(): void {
		$html = $this->submit( [
			'category_name' => 'PFCreateCategoryTest new',
			'default_form' => 'Category:Foo',
			'parent_category' => 'Form:Bar',
			'wpSave' => '1',
		] );

		$this->assertStringNotContainsString( 'wpTextbox1', $html );
		$this->assertSame( 2, substr_count( $html, '(htmlform-title-badnamespace' ) );
		$this->assertStringContainsString( 'value="PFCreateCategoryTest new"', $html );
	}

	public function testFormCreateActionChecksTitlePermissions(): void {
		// The user has the edit right, so PFCreateCategory's own check passes and only
		// the action's title-level check (namespace protection) can deny access.
		$this->overrideConfigValue( MainConfigNames::NamespaceProtection, [ NS_CATEGORY => [ 'pf-test-unassigned' ] ] );
		$user = $this->getTestUser()->getUser();
		RequestContext::getMain()->setUser( $user );
		$title = Title::makeTitle( NS_CATEGORY, 'PFCreateCategoryTest missing' );
		$context = new RequestContext();
		$context->setTitle( $title );
		$context->setUser( $user );
		$action = new PFHelperFormAction( Article::newFromTitle( $title, $context ), $context );

		$this->expectException( PermissionsError::class );
		$action->show();
	}
}
