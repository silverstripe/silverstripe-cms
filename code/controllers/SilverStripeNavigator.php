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
	 * @var DataObject|CMSPreviewable
	 */
	protected $record;
	
	/**
	 * @param DataObject $record
	 * @throws InvalidArgumentException if record doesn't implement CMSPreviewable
	 */
	public function __construct($record) {
		parent::__construct();
		if (!($record instanceof CMSPreviewable)) {
			throw new InvalidArgumentException(sprintf(
				'SilverStripeNavigator: Record of type %s doesn\'t implement CMSPreviewable',
				get_class($record)
			));
		}
		
		$this->record = $record;
	}

	/**
	 * @return SS_List of SilverStripeNavigatorItem
	 */
	public function getItems() {
		$items = array();
	
		$classes = ClassInfo::subclassesFor('SilverStripeNavigatorItem');
		array_shift($classes);
		
		// Sort menu items according to priority
		$i = 0;
		foreach($classes as $class) {
			// Skip base class
			if($class == 'SilverStripeNavigatorItem') continue;
			
			$i++;
			/** @var SilverStripeNavigatorItem $item */
			$item = new $class($this->record);
			if(!$item->canView()) continue;
			
			// This funny litle formula ensures that the first item added with the same priority will be left-most.
			$priority = $item->getPriority() * 100 - 1;
			
			// Ensure that we can have duplicates with the same (default) priority
			while(isset($items[$priority])) $priority++;
			
			$items[$priority] = $item;
		}
		ksort($items);

		// Drop the keys and let the ArrayList handle the numbering, so $First, $Last and others work properly.
		return new ArrayList(array_values($items));
	}
	
	/**
	 * @return DataObject
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * @param DataObject $record
	 * @return array template data
	 */
	static public function get_for_record($record) {
		$html = '';
		$message = '';
		$navigator = new SilverStripeNavigator($record);
		$items = $navigator->getItems();
		foreach($items as $item) {	
			$text = $item->getHTML();
			if($text) $html .= $text;
			$newMessage = $item->getMessage();
			if($newMessage && $item->isActive()) $message = $newMessage;
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
	 * @var DataObject|CMSPreviewable
	 */
	protected $record;
	
	/**
	 * @param DataObject $record
	 */
	public function __construct($record) {
		parent::__construct();
		$this->record = $record;
	}
	
	/**
	 * @return string HTML, mostly a link - but can be more complex as well.
	 * For example, a "future state" item might show a date selector.
	 */
	public function getHTML() {}

	/**
	* @return string
	* Get the Title of an item
	*/
	public function getTitle() {}
	
	/**
	 * Machine-friendly name.
	 */
	public function getName() {
		return substr(get_class($this), strpos(get_class($this), '_')+1);
	}

	/**
	 * Optional link to a specific view of this record.
	 * Not all items are simple links, please use {@link getHTML()}
	 * to represent an item in markup unless you know what you're doing.
	 * 
	 * @return string
	 */
	public function getLink() {}
	
	/**
	 * @return string
	 */
	public function getMessage() {}
	
	/**
	 * @return DataObject
	 */
	public function getRecord() {
		return $this->record;
	} 
	
	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->stat('priority');
	}
	
	/**
	 * As items might convey different record states like a "stage" or "live" table,
	 * an item can be active (showing the record in this state).
	 * 
	 * @return boolean
	 */
	public function isActive() {
		return false;
	}
	
	/**
	 * Filters items based on member permissions or other criteria,
	 * such as if a state is generally available for the current record.
	 * 
	 * @param Member
	 * @return Boolean
	 */
	public function canView($member = null) {
		return true;
	}

	/**
	 * Counts as "archived" if the current record is a different version from both live and draft.
	 * 
	 * @return boolean
	 */
	public function isArchived() {
		if(!$this->record->hasExtension('Versioned')) return false;
		
		if(!isset($this->record->_cached_isArchived)) {
			$baseTable = ClassInfo::baseDataClass($this->record->class);
			$currentDraft = Versioned::get_one_by_stage($baseTable, 'Stage', array(
				"\"$baseTable\".\"ID\"" => $this->record->ID
			));
			$currentLive = Versioned::get_one_by_stage($baseTable, 'Live', array(
				"\"$baseTable\".\"ID\"" => $this->record->ID
			));
			
			$this->record->_cached_isArchived = (
				(!$currentDraft || ($currentDraft && $this->record->Version != $currentDraft->Version)) 
				&& (!$currentLive || ($currentLive && $this->record->Version != $currentLive->Version))
			);
}

		return $this->record->_cached_isArchived;
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem {
	/** @config */
	private static $priority = 10;	
	
	public function getHTML() {
		return sprintf(
			'<a href="%s">%s</a>',
			$this->record->CMSEditLink(),
			_t('ContentController.CMS', 'CMS')
		);
	}
	
	public function getTitle() {
		return _t('ContentController.CMS', 'CMS', 'Used in navigation. Should be a short label');		
	}
	
	public function getLink() {
		return $this->record->CMSEditLink();
	}
	
	public function isActive() {
		return (Controller::curr() instanceof LeftAndMain);
	}
	
	public function canView($member = null) {
		return (
		// Don't show in CMS
			!(Controller::curr() instanceof LeftAndMain)
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem {
	/** @config */
	private static $priority = 20;

	public function getHTML() {
		$draftPage = $this->getDraftPage();
		if (!$draftPage) {
			return null;
		}

		$linkClass = $this->isActive() ? 'class="current" ' : '';
		$linkTitle = _t('ContentController.DRAFTSITE', 'Draft Site');
		$recordLink = Convert::raw2att(Controller::join_links($draftPage->AbsoluteLink(), "?stage=Stage"));
		return "<a {$linkClass} href=\"$recordLink\">$linkTitle</a>";
	}

	public function getTitle() {
		return _t('ContentController.DRAFT', 'Draft', 'Used for the Switch between draft and published view mode. Needs to be a short label');
	}
	
	public function getMessage() {
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.DRAFTSITE', 'Draft Site') ."</div>";
	}
	
	public function getLink() {
		$date = Versioned::current_archived_date();
		return Controller::join_links(
			$this->record->PreviewLink(), 
			'?stage=Stage',
			$date ? '?archiveDate=' . $date : null
		);
	}
	
	public function canView($member = null) {
		return (
			$this->record->hasExtension('Versioned') 
			&& $this->getDraftPage()
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}
	
	public function isActive() {
		return (
			Versioned::current_stage() == 'Stage' 
			&& !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())
			&& !$this->isArchived()
		);
	}
	
	protected function getDraftPage() {
		$baseTable = ClassInfo::baseDataClass($this->record->class);
		return Versioned::get_one_by_stage($baseTable, 'Stage', array(
			"\"$baseTable\".\"ID\"" => $this->record->ID
		));
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem {
	/** @config */
	private static $priority = 30;

	public function getHTML() {
		$livePage = $this->getLivePage();
		if (!$livePage) {
			return null;
		}

		$linkClass = $this->isActive() ? 'class="current" ' : '';
		$linkTitle = _t('ContentController.PUBLISHEDSITE', 'Published Site');
		$recordLink = Convert::raw2att(Controller::join_links($livePage->AbsoluteLink(), "?stage=Live"));
		return "<a {$linkClass} href=\"$recordLink\">$linkTitle</a>";
	}

	public function getTitle() {
		return _t('ContentController.PUBLISHED', 'Published', 'Used for the Switch between draft and published view mode. Needs to be a short label');
	}
	
	public function getMessage() {
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</div>";
	}
	
	public function getLink() {
		return Controller::join_links($this->record->PreviewLink(), '?stage=Live');
	}
	
	public function canView($member = null) {
		return (
			$this->record->hasExtension('Versioned') 
			&& $this->getLivePage()
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}
	
	public function isActive() {
		return (
			(!Versioned::current_stage() || Versioned::current_stage() == 'Live')
			&& !$this->isArchived()
		);
	}
	
	protected function getLivePage() {
		$baseTable = ClassInfo::baseDataClass($this->record->class);
		return Versioned::get_one_by_stage($baseTable, 'Live', array(
			"\"$baseTable\".\"ID\"" => $this->record->ID
		));
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem {
	/** @config */
	private static $priority = 40;

	public function getHTML() {
		$linkClass = $this->isActive() ? 'ss-ui-button current' : 'ss-ui-button';
		$linkTitle = _t('ContentController.ARCHIVEDSITE', 'Preview version');
		$recordLink = Convert::raw2att(Controller::join_links(
			$this->record->AbsoluteLink(),
			'?archiveDate=' . urlencode($this->record->LastEdited)
		));
		return "<a class=\"{$linkClass}\" href=\"$recordLink\" target=\"_blank\">$linkTitle</a>";
	}
	
	public function getTitle() {
		return _t('SilverStripeNavigator.ARCHIVED', 'Archived');
	}
	
	public function getMessage() { 
		$date = Versioned::current_archived_date();
		if (empty($date)) {
			return null;
		}

		/** @var SS_Datetime $dateObj */
			$dateObj = DBField::create_field('Datetime', $date);
		$title = _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors');
		return "<div id=\"SilverStripeNavigatorMessage\" title=\"{$title}\">"
			. _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from')
			. "<br />" . $dateObj->Nice() . "</div>";
		}
	
	public function getLink() {
		return Controller::join_links(
			$this->record->PreviewLink(),
			'?archiveDate=' . urlencode($this->record->LastEdited)
		);
	}
	
	public function canView($member = null) {
		return (
			$this->record->hasExtension('Versioned') 
			&& $this->isArchived()
			// Don't follow redirects in preview, they break the CMS editing form
			&& !($this->record instanceof RedirectorPage)
		);
	}
	
	public function isActive() {
		return $this->isArchived();
	}
	}
