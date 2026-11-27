<?php

/**
 * @group Database
 * @covers PFCreateForm
 */
class PFCreateFormTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new PFCreateForm();
	}

	public function testDeniesUsersWithoutEditRight(): void {
		$this->setGroupPermissions( '*', 'edit', false );

		$this->expectException( PermissionsError::class );
		$this->executeSpecialPage( '', null, null, new User() );
	}

	public function testDoesNotListExistingTemplates(): void {
		$this->editPage( 'Template:PFCreateFormTest existing', 'template' );

		[ $html ] = $this->executeSpecialPage( '', null, null, $this->getTestUser()->getUser() );

		$this->assertStringContainsString( 'pfAddTemplateDropdown', $html );
		$this->assertStringNotContainsString( 'PFCreateFormTest existing', $html );
	}
}
