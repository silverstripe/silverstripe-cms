<?php
/**
 * Reports section of the CMS.
 * 
 * All reports that should show in the ReportAdmin section
 * of the CMS need to subclass {@link SS_Report}, and implement
 * the appropriate methods and variables that are required.
 * 
 * @see SS_Report
 * 
 * @package cms
 * @subpackage reports
 */
class ReportAdmin extends LeftAndMain implements PermissionProvider {
	
	private static $url_segment = 'reports';
	
	private static $url_rule = '/$ReportClass/$Action';
	
	private static $menu_title = 'Reports';	
	
	private static $tree_class = 'SS_Report';

	private static $url_handlers = array(
		'$ReportClass/$Action' => 'handleAction'
	);

	/**
	 * Variable that describes which report we are currently viewing based on the URL (gets set in init method)
	 * @var String
	 */
	protected $reportClass;

	protected $reportObject;
	
	public function init() {
		parent::init();

		//set the report we are currently viewing from the URL
		$this->reportClass = (isset($this->urlParams['ReportClass'])) ? $this->urlParams['ReportClass'] : null;
		$allReports = SS_Report::get_reports();
		$this->reportObject = (isset($allReports[$this->reportClass])) ? $allReports[$this->reportClass] : null;

		Requirements::css(CMS_DIR . '/css/screen.css');

		// Set custom options for TinyMCE specific to ReportAdmin
		HtmlEditorConfig::get('cms')->setOption('ContentCSS', project() . '/css/editor.css');
		HtmlEditorConfig::get('cms')->setOption('Lang', i18n::get_tinymce_lang());

		// Always block the HtmlEditorField.js otherwise it will be sent with an ajax request
		Requirements::block(FRAMEWORK_DIR . '/javascript/HtmlEditorField.js');
		Requirements::javascript(CMS_DIR . '/javascript/ReportAdmin.js');
	}

	/**
	 * Does the parent permission checks, but also
	 * makes sure that instantiatable subclasses of
	 * {@link Report} exist. By default, the CMS doesn't
	 * include any Reports, so there's no point in showing
	 *
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
		if(!$member && $member !== FALSE) $member = Member::currentUser();

		if(!parent::canView($member)) return false;

		$hasViewableSubclasses = false;
		foreach($this->Reports() as $report) {
			if($report->canView($member)) return true;
		}

		return false;
	}

	/**
	 * Return a SS_List of SS_Report subclasses
	 * that are available for use.
	 *
	 * @return SS_List
	 */
	public function Reports() {
 		$output = new ArrayList();
		foreach(SS_Report::get_reports() as $report) {
			if($report->canView()) $output->push($report);
		}
		return $output;
	}

	/**
	 * Determine if we have reports and need
	 * to display the "Reports" main menu item
	 * in the CMS.
	 *
	 * The test for an existance of a report
	 * is done by checking for a subclass of
	 * "SS_Report" that exists.
	 *
	 * @return boolean
	 */
	static public function has_reports() {
		return sizeof(SS_Report::get_reports()) > 0;
	}

	/**
	 * Returns the Breadcrumbs for the ReportAdmin
	 * @param bool $unlinked
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);
		
		// The root element should explicitly point to the root node.
		// Uses session state for current record otherwise.
		$items[0]->Link = singleton('ReportAdmin')->Link();

		if ($this->reportObject) {
			//build breadcrumb trail to the current report
			$items->push(new ArrayData(array(
					'Title' => $this->reportObject->title(),
					'Link' => Controller::join_links($this->Link(), '?' . http_build_query(array('q' => $this->request->requestVar('q'))))
				)));
		}

		return $items;
	}

	/**
	 * Returns the link to the report admin section, or the specific report that is currently displayed
	 * @param string $action Action to link to.
	 * @return String
	 */
	public function Link($action = null) {
		$link = parent::Link($action);
		if ($this->reportObject) $link = $this->reportObject->getLink($action);
		return $link;
	}

	public function providePermissions() {
		$title = _t("ReportAdmin.MENUTITLE", LeftAndMain::menu_title_for_class($this->class));
		return array(
			"CMS_ACCESS_ReportAdmin" => array(
				'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			)
		);
	}

	public function getEditForm($id = null, $fields = null) {
		$report = $this->reportObject;
		if($report) {
			$fields = $report->getCMSFields();
		} else {
			// List all reports
			$fields = new FieldList();
			$gridFieldConfig = GridFieldConfig::create()->addComponents(
				new GridFieldToolbarHeader(),
				new GridFieldSortableHeader(),
				new GridFieldDataColumns(),
				new GridFieldFooter()
			);
			$gridField = new GridField('Reports',false, $this->Reports(), $gridFieldConfig);
			$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
			$columns->setDisplayFields(array(
				'title' => _t('ReportAdmin.ReportTitle', 'Title'),
			));
			$columns->setFieldFormatting(array(
				'title' => function($value, &$item) {
					return sprintf(
						'<a href="%s" class="cms-panel-link">%s</a>',
						Convert::raw2xml($item->Link),
						Convert::raw2xml($value)
					);
				}
			));
			$gridField->addExtraClass('all-reports-gridfield');
			$fields->push($gridField);
		}

		$actions = new FieldList();
		$form = CMSForm::create( 
			$this, "EditForm", $fields, $actions
		)->setHTMLID('Form_EditForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
		$form->addExtraClass('cms-edit-form cms-panel-padded center ' . $this->BaseCSSClasses());
		$form->loadDataFrom($this->request->getVars());

		$this->extend('updateEditForm', $form);

		return $form;
	}
}

