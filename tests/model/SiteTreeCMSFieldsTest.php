<?php
/**
 * @package cms
 * @subpackage tests
 */
class SiteTreeCMSFieldsTest extends SapphireTest {
	
	protected $usesDatabase = false;
	
	protected $illegalExtensions = array(
		'SiteTree' => array('SiteTreeSubsites')
	);
	
	protected $extraDataObjects = array(
		'SiteTreeTest_CMSFieldsBase',
		'SiteTreeTest_CMSFieldsChild',
		'SiteTreeTest_CMSFieldsGrandchild'
	);
	
	public function testPageFieldGeneration() {
		$page = new SiteTreeTest_CMSFieldsBase();
		$fields = $page->getCMSFields();
		$this->assertNotEmpty($fields);
		
		// Check basic field exists
		$this->assertNotEmpty($fields->dataFieldByName('PageField'));
	}
	
	public function testPageExtensionsFieldGeneration() {
		$page = new SiteTreeTest_CMSFieldsBase();
		$fields = $page->getCMSFields();
		$this->assertNotEmpty($fields);
		
		// Check extending fields exist
		$this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Not removed yet!
		$this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));
	}
	
	public function testSubpageFieldGeneration() {
		$page = new SiteTreeTest_CMSFieldsChild();
		$fields = $page->getCMSFields();
		$this->assertNotEmpty($fields);
		
		// Check extending fields exist
		$this->assertEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Removed by child class
		$this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));
		$this->assertNotEmpty($preExtendedField = $fields->dataFieldByName('ChildFieldBeforeExtension'));
		$this->assertEquals($preExtendedField->Title(), 'ChildFieldBeforeExtension: Modified Title');
		
		// Post-extension fields
		$this->assertNotEmpty($fields->dataFieldByName('ChildField'));
	}
	
	public function testSubSubpageFieldGeneration() {
		$page = new SiteTreeTest_CMSFieldsGrandchild();
		$fields = $page->getCMSFields();
		$this->assertNotEmpty($fields);
		
		// Check extending fields exist
		$this->assertEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Removed by child class
		$this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));
		
		// Check child fields removed by grandchild in beforeUpdateCMSFields
		$this->assertEmpty($fields->dataFieldByName('ChildFieldBeforeExtension')); // Removed by grandchild class
		
		// Check grandchild field modified by extension
		$this->assertNotEmpty($preExtendedField = $fields->dataFieldByName('GrandchildFieldBeforeExtension'));
		$this->assertEquals($preExtendedField->Title(), 'GrandchildFieldBeforeExtension: Modified Title');
		
		// Post-extension fields
		$this->assertNotEmpty($fields->dataFieldByName('ChildField'));
		$this->assertNotEmpty($fields->dataFieldByName('GrandchildField'));
	}
}

/**
 * Base class for CMS fields
 */
class SiteTreeTest_CMSFieldsBase extends SiteTree implements TestOnly {
	
	private static $db = array(
		'PageField' => 'Varchar(255)'
	);
	
	private static $extensions = array(
		'SiteTreeTest_CMSFieldsBaseExtension'
	);
	
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Test', new TextField('PageField'));
		return $fields;
	}
}

/**
 * Extension to top level test class, tests that updateCMSFields work
 */
class SiteTreeTest_CMSFieldsBaseExtension extends SiteTreeExtension implements TestOnly {
	private static $db = array(
		'ExtendedFieldKeep' => 'Varchar(255)',
		'ExtendedFieldRemove' => 'Varchar(255)'
	);
	
	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab('Root.Test', new TextField('ExtendedFieldRemove'));
		$fields->addFieldToTab('Root.Test', new TextField('ExtendedFieldKeep'));
		
		if($childField = $fields->dataFieldByName('ChildFieldBeforeExtension')) {
			$childField->setTitle('ChildFieldBeforeExtension: Modified Title');
		}
		
		if($grandchildField = $fields->dataFieldByName('GrandchildFieldBeforeExtension')) {
			$grandchildField->setTitle('GrandchildFieldBeforeExtension: Modified Title');
		}
	}
}

/**
 * Second level test class.
 * Tests usage of beforeExtendingCMSFields
 */
class SiteTreeTest_CMSFieldsChild extends SiteTreeTest_CMSFieldsBase implements TestOnly {
	private static $db = array(
		'ChildField' => 'Varchar(255)',
		'ChildFieldBeforeExtension' => 'Varchar(255)'
	);
	
	
	public function getCMSFields() {
		$this->beforeExtendingCMSFields(function(FieldList $fields) {
			$fields->addFieldToTab('Root.Test', new TextField('ChildFieldBeforeExtension'));
		});
		
		$fields = parent::getCMSFields();
		$fields->removeByName('ExtendedFieldRemove', true);
		$fields->addFieldToTab('Root.Test', new TextField('ChildField'));
		return $fields;
	}
}

/**
 * Third level test class, testing that beforeExtendingCMSFields can be nested
 */
class SiteTreeTest_CMSFieldsGrandchild extends SiteTreeTest_CMSFieldsChild implements TestOnly {
	private static $db = array(
		'GrandchildField' => 'Varchar(255)'
	);
	
	public function getCMSFields() {
		$this->beforeExtendingCMSFields(function(FieldList $fields) {
			// Remove field from parent's beforeExtendingCMSFields
			$fields->removeByName('ChildFieldBeforeExtension', true);
			
			// Adds own pre-extension field
			$fields->addFieldToTab('Root.Test', new TextField('GrandchildFieldBeforeExtension'));
		});
		
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Test', new TextField('GrandchildField'));
		return $fields;
	}
}
