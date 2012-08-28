<?php
/**
 * Utility class representing links to different views of a record
 * for CMS authors, usually for {@link SiteTree} objects with "stage" and "live" links.
 * Useful both in the CMS and alongside the page template (for logged in authors).
 * The class can be used for any {@link DataObject} subclass implementing the {@link CMSPreviewable} interface.
 * 
 * New item types can be defined by extending the {@link SilverStripeNavigatorItem} class,
 * for example the "cmsworkflow" module defines a new "future state" item with a date selector
 * to view embargoed data at a future point in time. So the item doesn't always have to be a simple link.
 * 
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigator extends ViewableData {
	
	/**
	 * @var DataObject
	 */
	protected $record;
	
	/**
	 * @param DataObject
	 */
	function __construct($record) {
		if(!in_array('CMSPreviewable', class_implements($record))) {
			throw new InvalidArgumentException('SilverStripeNavigator: Record of type %s doesn\'t implement CMSPreviewable', get_class($record));
		}
		
		$this->record = $record;
	}

	/**
	 * @return SS_List of SilverStripeNavigatorItem
	 */
	function getItems() {
		$items = array();
	
		$classes = ClassInfo::subclassesFor('SilverStripeNavigatorItem');
		array_shift($classes);
		
		// Sort menu items according to priority
		$i = 0;
		foreach($classes as $class) {
			// Skip base class
			if($class == 'SilverStripeNavigatorItem') continue;
			
			$i++;
			$item = new $class($this->record);
			if(!$item->canView()) continue;
			
			// This funny litle formula ensures that the first item added with the same priority will be left-most.
			$priority = $item->getPriority() * 100 - 1;
			
			// Ensure that we can have duplicates with the same (default) priority
			while(isset($items[$priority])) $priority++;
			
			$items[$priority] = $item;
		}
		ksort($items);

		return new ArrayList($items);
	}
	
	/**
	 * @return DataObject
	 */
	function getRecord() {
		return $this->record;
	}

	/**
	 * @param DataObject $record
	 * @return Array template data
	 */
	static function get_for_record($record) {
		$html = '';
		$message = '';
		$navigator = new SilverStripeNavigator($record);
		$items = $navigator->getItems();
		foreach($items as $item) {	
			$text = $item->getHTML();
			if($text) $html .= $text;
			$newMessage = $item->getMessage();
			if($newMessage) $message = $newMessage;
		}
		
		return array(
			'items' => $html,
			'message' => $message
		);
	}
}

/**
 * Navigator items are links that appear in the $SilverStripeNavigator bar.
 * To add an item, extend this class - it will be automatically picked up.
 * When instanciating items manually, please ensure to call {@link canView()}.
 * 
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem extends ViewableData {
	
	/**
	 * @param DataObject
	 */
	protected $record;
	
	/**
	 * @param DataObject
	 */
	function __construct($record) {
		$this->record = $record;
	}
	
	/**
	 * @return String HTML, mostly a link - but can be more complex as well.
	 * For example, a "future state" item might show a date selector.
	 */
	function getHTML() {}

	/**
	* @return String
	* Text displayed in watermark
	*/
	function getWatermark() {}
	
	/**
	 * Optional link to a specific view of this record.
	 * Not all items are simple links, please use {@link getHTML()}
	 * to represent an item in markup unless you know what you're doing.
	 * 
	 * @return String
	 */
	function getLink() {}
	
	/**
	 * @return String
	 */
	function getMessage() {}
	
	/**
	 * @return DataObject
	 */
	function getRecord() {
		return $this->record;
	} 
	
	/**
	 * @return Int
	 */
	function getPriority() {
		return $this->stat('priority');
	}
	
	/**
	 * As items might convey different record states like a "stage" or "live" table,
	 * an item can be active (showing the record in this state).
	 * 
	 * @return boolean
	 */
	function isActive() {
		return false;
	}
	
	/**
	 * Filters items based on member permissions or other criteria,
	 * such as if a state is generally available for the current record.
	 * 
	 * @param Member
	 * @return Boolean
	 */
	function canView($member = null) {
		return true;
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem {
	static $priority = 10;	
	
	function getHTML() {
		return sprintf(
			'<a href="%s">%s</a>',
			$this->record->CMSEditLink(),
			_t('ContentController.CMS', 'CMS')
		);
	}
	
	function getLink() {
		return $this->record->CMSEditLink();
	}
	
	function isActive() {
		return (Controller::curr() instanceof CMSMain);
	}
	
	function canView($member = null) {
		// Don't show in CMS
		return !(Controller::curr() instanceof CMSMain);
	}

}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem {
	static $priority = 20;

	function getHTML() {
		$draftPage = $this->getDraftPage();
		if($draftPage) {
			$this->recordLink = Controller::join_links($draftPage->AbsoluteLink(), "?stage=Stage");
			return "<a href=\"$this->recordLink\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		}
	}

	function getWatermark() {
		return _t('ContentController.DRAFTSITE');
	}
	
	function getMessage() {
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.DRAFTSITE', 'Draft Site') ."</div>";
	}
	
	function getLink() {
		return Controller::join_links($this->record->AbsoluteLink(), '?stage=Stage');
	}
	
	function canView($member = null) {
		return ($this->record->hasExtension('Versioned') && $this->getDraftPage());
	}
	
	function isActive() {
		return (
			Versioned::current_stage() == 'Stage' 
			&& !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())
		);
	}
	
	protected function getDraftPage() {
		$baseTable = ClassInfo::baseDataClass($this->record->class);
		return Versioned::get_one_by_stage(
			$baseTable, 
			'Stage', 
			sprintf('"%s"."ID" = %d', $baseTable, $this->record->ID)
		);
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem {
	static $priority = 30;

	function getHTML() {
		$livePage = $this->getLivePage();
		if($livePage) {
			$this->recordLink = Controller::join_links($livePage->AbsoluteLink(), "?stage=Live");
			return "<a href=\"$this->recordLink\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
		}
	}

	function getWatermark() {
		return _t('ContentController.PUBLISHEDSITE');
	}
	
	function getMessage() {
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</div>";
	}
	
	function getLink() {
		return Controller::join_links($this->record->AbsoluteLink(), '?stage=Live');
	}
	
	function canView($member = null) {
		return ($this->record->hasExtension('Versioned') && $this->getLivePage());
	}
	
	function isActive() {
		return (!Versioned::current_stage() || Versioned::current_stage() == 'Live');
	}
	
	protected function getLivePage() {
		$baseTable = ClassInfo::baseDataClass($this->record->class);
		return Versioned::get_one_by_stage(
			$baseTable, 
			'Live', 
			sprintf('"%s"."ID" = %d', $baseTable, $this->record->ID)
		);
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem {
	static $priority = 40;

	function getHTML() {
			$this->recordLink = $this->record->AbsoluteLink();
			return "<a class=\"ss-ui-button\" href=\"$this->recordLink?archiveDate={$this->record->LastEdited}\" target=\"_blank\">". _t('ContentController.ARCHIVEDSITE', 'Preview version') ."</a>";
	}
	
	function getMessage() { 
		if($date = Versioned::current_archived_date()) {
			$dateObj = Datetime::create();
			$dateObj->setValue($date);
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">". _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from') ."<br>" . $dateObj->Nice() . "</div>";
		}
	}
	
	function getLink() {
		return $this->record->AbsoluteLink() . '?archiveDate=' . $this->record->LastEdited;
	}
	
	function canView($member = null) {
		return ($this->record->hasExtension('Versioned') && $this->isArchived());
	}
	
	function isActive() {
		return (Versioned::current_archived_date());
	}
	
	/**
	 * Counts as "archived" if the current record is a different version from both live and draft.
	 * 
	 * @return boolean
	 */
	function isArchived() {
		if(!$this->record->hasExtension('Versioned')) return false;
		
		$baseTable = ClassInfo::baseDataClass($this->record->class);
		$currentDraft = Versioned::get_one_by_stage(
			$baseTable, 
			'Stage', 
			sprintf('"%s"."ID" = %d', $baseTable, $this->record->ID)
		);
		$currentLive = Versioned::get_one_by_stage(
			$baseTable, 
			'Live', 
			sprintf('"%s"."ID" = %d', $baseTable, $this->record->ID)
		);
		return (
			(!$currentDraft || ($currentDraft && $this->record->Version != $currentDraft->Version)) 
			&& (!$currentLive || ($currentLive && $this->record->Version != $currentLive->Version))
		);
	}
}

