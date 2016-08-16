<?php
namespace SilverStripe\CMS\Forms;

/**
 * Readonly version of a site tree URL segment field
 *
 * @package forms
 * @subpackage fields-basic
 */
class SiteTreeURLSegmentField_Readonly extends SiteTreeURLSegmentField
{
	protected $readonly = true;

	public function performReadonlyTransformation()
	{
		return clone $this;
	}
}
