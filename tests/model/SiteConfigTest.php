<?php
/**
 * @package cms
 * @subpackage tests
 * 
 * Note: Most of the permission-related SiteConfig tests are located in 
 * SiteTreePermissionsTest
 */
class SiteConfigTest extends SapphireTest {
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	function testAvailableThemes() {
		$config = SiteConfig::current_site_config();
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
		
		SiteConfig::disable_theme('darkshades');
		$themes = $config->getAvailableThemes($testThemeBaseDir);
		$this->assertFalse(in_array('darkshades', $themes), 'Darkshades was disabled - it is no longer available');
		
		Filesystem::removeFolder($testThemeBaseDir);
	}
	
}
