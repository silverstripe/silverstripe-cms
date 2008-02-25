<?php

/**
 * @package cms
 * @subpackage bulkloading
 */

/**
 * An abstract base for bulk loaders of content into the SilverStripe database.
 * Bulk loaders give SilverStripe authors the ability to do large-scale CSV uploads into their Sapphire databases.
 * @package cms
 * @subpackage bulkloading
 */
abstract class BulkLoader extends ViewableData {
	/**
	 * Override this on subclasses to give the specific functions names
	 */
	static $title = null;

	/**
	 * Return a human-readable name for this object.
	 * It defaults to the class name can be overridden by setting the static variable $title
	 */
	function Title() {
		if($title = $this->stat('title')) return $title;
		else return $this->class;
	}
	
	/**
	 * Process every record in the file
	 * @param filename The name of the CSV file to process
	 * @param preview If true, we'll just output a summary of changes but not actually do anything
	 *
	 * @returns A DataObjectSet containing a list of all the reuslst
	 */
	function processAll($filename, $preview = false) {
		// TODO
		// Get the first record out of the CSV and store it as headers
		// Get each record out of the CSV
		//		Remap the record so that it's keyed by headers
		//		Pass it to $this->processRecord, and get the results
		//		Put the results inside an ArrayData and push that onto a DataObjectSet for returning
	}
	

	/*----------------------------------------------------------------------------------------
	 * Next, we have some abstract functions that let subclasses say what kind of batch operation they're 
	 * going to do
	 *----------------------------------------------------------------------------------------
	 */
	
	
	/**
	 * Return a FieldSet containing all the options for this form; this
	 * doesn't include the actual upload field itself
	 */
	abstract function getOptionFields();
	
	/**
	 * Process a single record from the CSV file.
	 * @param record An map of the CSV data, keyed by the header field
	 * @param preview 
	 * 
	 * @returns A 2 value array.  
	 *   - The first element should be "add", "edit" or "", depending on the operation performed in response to this record
	 *   - The second element is a free-text string that can optionally provide some more information about what changes have
	 *     been made	 
	 */
	abstract function processRecord($record, $preview = false);
	
	/*----------------------------------------------------------------------------------------
	 * Next, we have a library of helper functions (Brian to build as necessary)
	 *----------------------------------------------------------------------------------------
	 */

}

?>