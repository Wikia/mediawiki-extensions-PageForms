<?php

use MediaWiki\Request\FauxRequest;

/**
 * @group Database
 * @covers PFCreateForm
 * @covers PFUtils::normalizeTitleInput
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

	public function testShowsNameInputWithoutPresetName(): void {
		$this->editPage( 'Template:PFCreateFormTest existing', 'template' );

		[ $html ] = $this->executeSpecialPage( null, null, null, $this->getTestUser()->getUser() );

		$this->assertStringContainsString( 'name="form_name"', $html );
		$this->assertStringContainsString( 'name="new_template"', $html );
	}

	public function testAddsTemplateTypedWithNamespacePrefix(): void {
		$this->editPage( 'Template:PFCreateFormTest existing', 'template' );

		[ $html ] = $this->executeSpecialPage( null, new FauxRequest( [
			'new_template' => 'Template:PFCreateFormTest existing',
			'add_field' => 'true',
		], true ), null, $this->getTestUser()->getUser() );

		$this->assertStringContainsString( 'name="template_0"', $html );
		$this->assertStringContainsString( 'value="PFCreateFormTest existing"', $html );
	}

	public function testRejectsMissingTemplate(): void {
		$this->editPage( 'Template:PFCreateFormTest existing', 'template' );

		[ $html ] = $this->executeSpecialPage( null, new FauxRequest( [
			'new_template' => 'PFCreateFormTest missing',
			'add_field' => 'true',
		], true ), null, $this->getTestUser()->getUser() );

		$this->assertStringNotContainsString( 'name="template_0"', $html );
		$this->assertStringContainsString( '(htmlform-title-not-exists', $html );
	}
}
