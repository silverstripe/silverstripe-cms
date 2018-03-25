<?php

namespace SilverStripe\CMS\Model;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Shortcodes\FileLink;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * @deprecated 4.2..5.0 Will be removed in cms 5.0
 */
class SiteTreeFolderExtension extends DataExtension
{
    public function __construct()
    {
        parent::__construct();
        Deprecation::notice('5.0', 'Will be removed in 5.0');
    }


    /**
     * Looks for files used in system and create where clause which contains all ID's of files.
     *
     * @deprecated 4.2..5.0
     * @returns string where clause which will work as filter.
     */
    public function getUnusedFilesListFilter()
    {
        Deprecation::notice('5.0', 'Will be removed in 5.0');

        // Add all records in link tracking
        $usedFiles = FileLink::get()->column('LinkedID');

        // Get all classes that aren't folder
        $fileClasses = array_diff_key(
            ClassInfo::subclassesFor(File::class),
            ClassInfo::subclassesFor(Folder::class)
        );

        // Search on a class-by-class basis
        $classes = ClassInfo::subclassesFor(SiteTree::class);

        $schema = DataObject::getSchema();
        foreach ($classes as $className) {
            // Build query based on all direct has_ones on this class
            $hasOnes = Config::inst()->get($className, 'has_one', Config::UNINHERITED);
            if (empty($hasOnes)) {
                continue;
            }
            $where = [];
            $columns = [];
            foreach ($hasOnes as $relName => $joinClass) {
                if (in_array($joinClass, $fileClasses)) {
                    $column = $relName . 'ID';
                    $columns[] = $column;
                    $quotedColumn = $schema->sqlColumnForField($className, $column);
                    $where[] = "{$quotedColumn} > 0";
                }
            }

            // Get all records with any file ID in the searched columns
            $recordsArray = DataList::create($className)->whereAny($where)->toArray();
            $records = ArrayList::create($recordsArray);
            foreach ($columns as $column) {
                $usedFiles = array_unique(array_merge($usedFiles, $records->column($column)));
            }
        }

        // Create filter based on class and id
        $classFilter = sprintf(
            "(\"File\".\"ClassName\" IN (%s))",
            implode(", ", Convert::raw2sql($fileClasses, true))
        );
        if ($usedFiles) {
            return "\"File\".\"ID\" NOT IN (" . implode(', ', $usedFiles) . ") AND $classFilter";
        } else {
            return $classFilter;
        }
    }
}
