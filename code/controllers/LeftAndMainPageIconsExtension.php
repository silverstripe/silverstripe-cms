<?php
/**
 * Extension to include custom page icons
 * 
 * @package cms
 * @subpackage controller
 */
class LeftAndMainPageIconsExtension extends Extension {

	public function init() {
		Requirements::customCSS($this->generatePageIconsCss());
	}

	/**
	 * Include CSS for page icons. We're not using the JSTree 'types' option
	 * because it causes too much performance overhead just to add some icons.
	 * 
	 * @return String CSS 
	 */
	public function generatePageIconsCss() {
		$css = ''; 
		
		$classes = ClassInfo::subclassesFor('SiteTree'); 
		foreach($classes as $class) {
			$obj = singleton($class); 
			$iconSpec = $obj->stat('icon'); 

			if(!$iconSpec) continue;

			// Legacy support: We no longer need separate icon definitions for folders etc.
			$iconFile = (is_array($iconSpec)) ? $iconSpec[0] : $iconSpec;

			// Legacy support: Add file extension if none exists
			if(!pathinfo($iconFile, PATHINFO_EXTENSION)) $iconFile .= '-file.gif';

			$iconPathInfo = pathinfo($iconFile); 
			
			// Base filename 
			$baseFilename = $iconPathInfo['dirname'] . '/' . $iconPathInfo['filename'];
			$fileExtension = $iconPathInfo['extension'];

			$selector = ".page-icon.class-$class, li.class-$class > a .jstree-pageicon";

			if(Director::fileExists($iconFile)) {
				$css .= "$selector { background: transparent url('$iconFile') 0 0 no-repeat; }\n";
			} else {
				// Support for more sophisticated rules, e.g. sprited icons
				$css .= "$selector { $iconFile }\n";
			}
		}

		return $css;
	}

}