<?php
/**
 * Utility class representing links to different views of a record
 * for CMS authors, usually for {@link SiteTree} objects with "stage" and "live" links.
 * Useful both in the CMS and alongside the page template (for logged in authors).
 * 
 * New item types can be defined by extending the {@link SilverStripeNavigatorItem} class,
 * for example the "cmsworkflow" module defines a new "future state" item with a date selector
 * to view embargoed data at a future point in time. So the item doesn't always have to be a simple link.
 * 
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigator {
	
	/**
	 * @var DataObject
	 */
	protected $record;
	
	/**
	 * @param DataObject
	 */
	function __construct($record) {
		$this->record = $record;
	}

	/**
	 * @return DataObjectSet of SilverStripeNavigatorItem
	 */
	function getItems() {
		$items = '';
	
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
		
		return new DataObjectSet($items);
	}
	
	/**
	 * @return DataObject
	 */
	function getRecord() {
		return $this->record;
	}

	/**
	 * @param SiteTree $record
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
 * To add an item, extends this class.
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
	 * Hence there's no getLink() method.
	 */
	function getHTML() {}
	
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
	 * Filters items based on member permissions or other criteria.
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
		if(is_a(Controller::curr(), 'CMSMain')) {
			return '<a class="current">CMS</a>';
		} else {
			$cmsLink = 'admin/show/' . $this->record->ID;
			$cmsLink = "<a href=\"$cmsLink\">". _t('ContentController.CMS', 'CMS') ."</a>";
	
			return $cmsLink;
		}
	}
	
	function getLink() {
		if(is_a(Controller::curr(), 'CMSMain')) {
			return Controller::curr()->AbsoluteLink('show') . $this->record->ID;
		}
	}

}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem {
	static $priority = 20;

	function getHTML() {
		if(Versioned::current_stage() == 'Stage' && !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())) {
			return "<a class=\"current\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		} else {
			$draftPage = Versioned::get_one_by_stage('SiteTree', 'Stage', '"SiteTree"."ID" = ' . $this->record->ID);
			if($draftPage) {
				$this->recordLink = Controller::join_links($draftPage->AbsoluteLink(), "?stage=Stage");
				return "<a href=\"$this->recordLink\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
			}
		}
	}
	
	function getMessage() {
		if(Versioned::current_stage() == 'Stage') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.DRAFTSITE', 'Draft Site') ."</div>";
		}
	}
	
	function getLink() {
		if(Versioned::current_stage() == 'Stage') {
			return Controller::join_links($this->record->AbsoluteLink(), '?stage=Stage');
		}
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem {
	static $priority = 30;

	function getHTML() {
		if(Versioned::current_stage() == 'Live') {
			return "<a class=\"current\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
		} else {
			$livePage = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $this->record->ID);
			if($livePage) {
				$this->recordLink = Controller::join_links($livePage->AbsoluteLink(), "?stage=Live");
				return "<a href=\"$this->recordLink\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
			}
		}
	}
	
	function getMessage() {
		if(Versioned::current_stage() == 'Live') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</div>";
		}
	}
	
	function getLink() {
		if(Versioned::current_stage() == 'Live') {
			return Controller::join_links($this->record->AbsoluteLink(), '?stage=Live');
		}
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem {
	static $priority = 40;

	function getHTML() {
		if(Versioned::current_archived_date()) {
			return "<a class=\"current\">". _t('ContentController.ARCHIVEDSITE', 'Archived Site') ."</a>";
		} else {
			// Display the archive link if the page currently displayed in the CMS is other version than live and draft
			$currentDraft = Versioned::get_one_by_stage('SiteTree', 'Draft', '"SiteTree"."ID" = ' . $this->record->ID);
			$currentLive = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $this->record->ID);
			if(
				(!$currentDraft || ($currentDraft && $this->record->Version != $currentDraft->Version)) 
				&& (!$currentLive || ($currentLive && $this->record->Version != $currentLive->Version))
			) {
				$this->recordLink = $this->record->AbsoluteLink();
				return "<a href=\"$this->recordLink?archiveDate={$this->record->LastEdited}\" target=\"_blank\">". _t('ContentController.ARCHIVEDSITE', 'Archived Site') ."</a>";
			}
		}
	}
	
	function getMessage() {
		if($date = Versioned::current_archived_date()) {
			$dateObj = Object::create('Datetime');
			$dateObj->setValue($date);
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">". _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from') ."<br>" . $dateObj->Nice() . "</div>";
		}
	}
	
	function getLink() {
		if($date = Versioned::current_archived_date()) {
			return $this->record->AbsoluteLink() . '?archiveDate=' . $date;
		}
	}
}

?>