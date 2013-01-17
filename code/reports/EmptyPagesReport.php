<?php

/**
 * @package cms
 * @subpackage reports
 */
class EmptyPagesReport extends SS_Report {

	public function title() {
		return _t('SideReport.EMPTYPAGES',"Pages with no content");
	}

	public function group() {
		return _t('SideReport.ContentGroupTitle', "Content reports");
	}

	public function sort() {
		return 100;
	}

	public function sourceRecords($params = null) {
		return SiteTree::get()->where(
			"\"ClassName\" != 'RedirectorPage' AND (\"Content\" = '' OR \"Content\" IS NULL OR \"Content\" LIKE '<p></p>' OR \"Content\" LIKE '<p>&nbsp;</p>')"
		)->sort('Title');
	}

	public function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}
}