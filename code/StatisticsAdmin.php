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
		return $this->Trend();
	}



	function Trend() {
		return Statistics::TrendChart(array('Member', 'SiteTree', 'Group'), 'day', 'mchart', 'Line', 'red');
	}

	function BrowserPie() {
		return Statistics::BrowserChart();
	}

	function UserTable() {
		//Statistics::getBrowserChart();
		return Statistics::UserRecordTable();
	}

	function ViewTable() {
		return Statistics::getViews('all');
	}

	public function users($params) {
		return Statistics::UserRecordTable();
	}

	public function overview($params) {
		return $this->BrowserPie();
	}

}

?>
