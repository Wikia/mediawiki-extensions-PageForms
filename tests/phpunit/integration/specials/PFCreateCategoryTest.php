<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Title\Title;

/**
 * @group Database
 * @covers PFCreateCategory
 * @covers PFHelperFormAction
 */
class PFCreateCategoryTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new PFCreateCategory();
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

	public function testFormCreateActionDeniesUsersWithoutEditRight(): void {
		$this->setGroupPermissions( '*', 'edit', false );
		$title = Title::makeTitle( NS_CATEGORY, 'PFCreateCategoryTest missing' );
		$context = new RequestContext();
		$context->setTitle( $title );
		$context->setUser( new User() );
		$action = new PFHelperFormAction( Article::newFromTitle( $title, $context ), $context );

		$this->expectException( PermissionsError::class );
		$action->show();
	}
}
