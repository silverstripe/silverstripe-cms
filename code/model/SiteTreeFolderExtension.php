<?php
class SiteTreeFolderExtension extends DataExtension {
	
	function updateCMSFields(&$fields) {
		// TODO commenting out unused files tab till bugs are fixed
		// $fields->push(new Tab("UnusedFiles", _t('Folder.UNUSEDFILESTAB', "Unused files"),
		// 	new Folder_UnusedAssetsField($this)
		// ));
	}
	
	/**
     * Looks for files used in system and create where clause which contains all ID's of files.
     * 
     * @returns String where clause which will work as filter.
     */
	public function getUnusedFilesListFilter() {
		$result = DB::query("SELECT DISTINCT \"FileID\" FROM \"SiteTree_ImageTracking\"");
		$usedFiles = array();
		$where = '';
		$classes = ClassInfo::subclassesFor('SiteTree');
		
		if($result->numRecords() > 0) {
			while($nextResult = $result->next()) {
				$where .= $nextResult['FileID'] . ','; 
			}
		}

		foreach($classes as $className) {
			$query = singleton($className)->extendedSQL();
			$ids = $query->execute()->column();
			if(!count($ids)) continue;
			
			foreach(singleton($className)->has_one() as $relName => $joinClass) {
				if($joinClass == 'Image' || $joinClass == 'File') {
					$fieldName = $relName .'ID';
					$query = singleton($className)->extendedSQL("$fieldName > 0");
					$query->distinct = true;
					$query->select = array($fieldName);
					$usedFiles = array_merge($usedFiles, $query->execute()->column());

				} elseif($joinClass == 'Folder') {
 					// @todo
				}
			}
		}
		
		if($usedFiles) {
 			return "\"File\".\"ID\" NOT IN (" . implode(', ', $usedFiles) . ") AND (\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";

		} else {
			return "(\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";
		}
		return $where;
	}
}