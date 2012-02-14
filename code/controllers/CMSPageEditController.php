<?php

/**
 * @package cms
 */
class CMSPageEditController extends CMSMain {

	static $url_segment = 'page/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 41;
}