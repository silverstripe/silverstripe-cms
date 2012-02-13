<?php
/**
 * @package cms
 * @subpackage tasks
 */
class SiteTreeMaintenanceTask extends Controller {
	static $allowed_actions = array(
		'*' => 'ADMIN'
	);
	
	function makelinksunique() {
		$badURLs = "'" . implode("', '", DB::query("SELECT URLSegment, count(*) FROM SiteTree GROUP BY URLSegment HAVING count(*) > 1")->column()) . "'";
		$pages = DataObject::get("SiteTree", "\"URLSegment\" IN ($badURLs)");

		foreach($pages as $page) {
			echo "<li>$page->Title: ";
			$urlSegment = $page->URLSegment;
			$page->write();
			if($urlSegment != $page->URLSegment) {
				echo sprintf(_t('SiteTree.LINKSCHANGEDTO', " changed %s -> %s"), $urlSegment, $page->URLSegment);
			}
			else {
				echo sprintf(_t('SiteTree.LINKSALREADYUNIQUE', " %s is already unique"), $urlSegment);
			}
			die();
		}
	}
}
