<?php
/**
 * @package cms
 * @subpackage tests
 * 
 * Note: Most of the permission-related SiteConfig tests are located in 
 * SiteTreePermissionsTest
 */
class SiteConfigTest extends SapphireTest {

	protected static $fixture_file = 'SiteConfigTest.yml';
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	public function testAvailableThemes() {
		$config = $this->objFromFixture('SiteConfig', 'default');
		$ds = DIRECTORY_SEPARATOR;
		$testThemeBaseDir = TEMP_FOLDER . $ds . 'test-themes';
		
		if(file_exists($testThemeBaseDir)) Filesystem::removeFolder($testThemeBaseDir);
		mkdir($testThemeBaseDir);
		mkdir($testThemeBaseDir . $ds . 'blackcandy');
		mkdir($testThemeBaseDir . $ds . 'blackcandy_blog');
		mkdir($testThemeBaseDir . $ds . 'darkshades');
		mkdir($testThemeBaseDir . $ds . 'darkshades_blog');
		
		$themes = $config->getAvailableThemes($testThemeBaseDir);
		$this->assertContains('blackcandy', $themes, 'Test themes contain blackcandy theme');
		$this->assertContains('darkshades', $themes, 'Test themes contain darkshades theme');
		
		SiteConfig::config()->disabled_themes = array('darkshades');
		$themes = $config->getAvailableThemes($testThemeBaseDir);
		$this->assertFalse(in_array('darkshades', $themes), 'Darkshades was disabled - it is no longer available');
		
		Filesystem::removeFolder($testThemeBaseDir);
	}

	public function testCanCreateRootPages() {
		$config = $this->objFromFixture('SiteConfig', 'default');

		// Log in without pages admin access
		$this->logInWithPermission('CMS_ACCESS_AssetAdmin');
		$this->assertFalse($config->canCreateTopLevel());

		// Login with necessary edit permission
		$perms = SiteConfig::config()->required_permission;
		$this->logInWithPermission(reset($perms));
		$this->assertTrue($config->canCreateTopLevel());
	}

	public function testCanViewPages() {
		$config = $this->objFromFixture('SiteConfig', 'default');
		$this->assertTrue($config->canViewPages());
	}

	public function testCanEdit() {
		$config = $this->objFromFixture('SiteConfig', 'default');
		
		// Unrelated permissions don't allow siteconfig
		$this->logInWithPermission('CMS_ACCESS_AssetAdmin');
		$this->assertFalse($config->canEdit());

		// Only those with edit permission can do this
		$this->logInWithPermission('EDIT_SITECONFIG');
		$this->assertTrue($config->canEdit());
	}

	public function testCanEditPages() {
		$config = $this->objFromFixture('SiteConfig', 'default');
		
		// Log in without pages admin access
		$this->logInWithPermission('CMS_ACCESS_AssetAdmin');
		$this->assertFalse($config->canEditPages());

		// Login with necessary edit permission
		$perms = SiteConfig::config()->required_permission;
		$this->logInWithPermission(reset($perms));
		$this->assertTrue($config->canEditPages());
	}


	
}
