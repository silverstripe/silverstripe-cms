<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataExtension;

/**
 * @todo Cleanup, refactor, test this class
 */
class SiteTreeFolderExtension extends DataExtension {

    /**
     * Looks for files used in system and create where clause which contains all ID's of files.
     *
     * @returns String where clause which will work as filter.
     */
    public function getUnusedFilesListFilter() {
        $result = DB::query("SELECT DISTINCT \"FileID\" FROM \"SiteTree_ImageTracking\"");
        $usedFiles = array();
        $where = '';
        $classes = ClassInfo::subclassesFor('SilverStripe\\CMS\\Model\\SiteTree');

        if($result->numRecords() > 0) {
            while($nextResult = $result->next()) {
                $where .= $nextResult['FileID'] . ',';
            }
        }

        foreach($classes as $className) {
            $query = new DataQuery($className);
            $ids = $query->execute()->column();
            if(!count($ids)) continue;

            foreach(singleton($className)->hasOne() as $relName => $joinClass) {
                if($joinClass == 'SilverStripe\\Assets\\Image' || $joinClass == 'SilverStripe\\Assets\\File') {
                    $fieldName = $relName .'ID';
                    $query = DataList::create($className)->where("$fieldName > 0");
                    $query->distinct = true;
                    $query->select(array($fieldName));
                    $usedFiles = array_merge($usedFiles, $query->execute()->column());

                } elseif($joinClass == 'SilverStripe\\Assets\\Folder') {
                    // @todo
                }
            }
        }

        if($usedFiles) {
            return "\"File\".\"ID\" NOT IN (" . implode(', ', $usedFiles) . ") AND (\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";

        } else {
            return "(\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";
        }
        return $where; // @todo - How?
    }
}
