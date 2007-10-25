<?php

class StatisticsAdmin extends LeftAndMain {
	static $tree_class = "SiteTree";
	static $subitem_class = "Member";
	var $charts = array();

	/**
	 * Initialisation method called before accessing any functionality that BulkLoaderAdmin has to offer
	 */
	public function init() {
		parent::init();

		//Get rid of prototype 1.4.x
		Requirements::clear();

		//Load prototype 1.5.x
		Requirements::javascript("jsparty/prototype15.js");

		//Restore needed requirements
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/loader.js");

		Requirements::javascript("jsparty/layout_helpers.js");
		Requirements::javascript("jsparty/tree/tree.js");
		Requirements::css("jsparty/tree/tree.css");
		Requirements::javascript("jsparty/scriptaculous/effects.js");
		Requirements::javascript("jsparty/scriptaculous/dragdrop.js");

		Requirements::javascript("jsparty/tabstrip/tabstrip.js");
		Requirements::css("jsparty/tabstrip/tabstrip.css");


		Requirements::css("jsparty/greybox/greybox.css");
		Requirements::javascript("jsparty/greybox/AmiJS.js");
		Requirements::javascript("jsparty/greybox/greybox.js");

		Requirements::javascript("cms/javascript/LeftAndMain.js");
		Requirements::javascript("cms/javascript/LeftAndMain_left.js");
		Requirements::javascript("cms/javascript/LeftAndMain_right.js");

		Requirements::javascript("jsparty/calendar/calendar.js");
		Requirements::javascript("jsparty/calendar/lang/calendar-en.js");
		Requirements::javascript("jsparty/calendar/calendar-setup.js");
		Requirements::css("sapphire/css/CalendarDateField.css");
		Requirements::css("jsparty/calendar/calendar-win2k-1.css");

		Requirements::javascript('sapphire/javascript/Validator.js');

		Requirements::css("sapphire/css/SubmittedFormReportField.css");

		Requirements::css('cms/css/TinyMCEImageEnhancement.css');
		Requirements::javascript("jsparty/SWFUpload/SWFUpload.js");
		Requirements::javascript("cms/javascript/Upload.js");
		Requirements::javascript("sapphire/javascript/Security_login.js");
		Requirements::javascript('cms/javascript/TinyMCEImageEnhancement.js');


		//Load statistics requirements
		Requirements::javascript("jsparty/plotr.js");
		Requirements::javascript("jsparty/tablesort.js");
		Requirements::javascript("cms/javascript/StatisticsAdmin.js");

		Requirements::css("cms/css/StatisticsAdmin.css");
	}

	public function Link($action=null) {
		return "admin/statistics/$action";
	}

	/**
	 * Form that will be shown when we open one of the items
	 */
	public function EditForm() {
		return "<div id=\"bovs\">\n
		<h1>Select a report type from the left for a detailed look at site statistics</h1>\n\n" .
		$this->RecentViews() .
		"\n\n</div>\n\n" .
		$this->showAll();
	}

	function RecentViews() {
		return Statistics::get_recent_views();
	}

	function Trend() {
		return Statistics::trend_chart(array('PageView', 'Member', 'SiteTree'), 'day', 'mchart', 'Line', 'red');
	}

	function BrowserPie() {
		return Statistics::browser_chart();
	}

	function OSPie() {
		return Statistics::os_chart();
	}

	function UACPie() {
		return Statistics::activity_chart();
	}

	function UserTable() {
		return Statistics::user_record_table();
	}

	function ViewTable() {
		return Statistics::get_views('all');
	}



	function showAll() {
		return $this->BrowserPie() .
		$this->OSPie() .
		$this->UACPie() .
		$this->Trend() .
		$this->UserTable() .
		$this->ViewTable();
	}

	public function viewcsv() {
		header("Content-type: application/x-msdownload");
		header("Content-Disposition: attachment; filename=viewreport.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo Statistics::get_view_csv();
	}

	public function usercsv() {
		header("Content-type: application/x-msdownload");
		header("Content-Disposition: attachment; filename=userreport.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo Statistics::get_user_csv();
	}

}

?>
