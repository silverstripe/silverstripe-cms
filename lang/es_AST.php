<?php

/**
 *  language pack
 * @package cms
 * @subpackage i18n
 */

i18n::include_locale_file('cms', 'en_US');

global $lang;

if(array_key_exists('es_AST', $lang) && is_array($lang['es_AST'])) {
	$lang['es_AST'] = array_merge($lang['en_US'], $lang['es_AST']);
} else {
	$lang['es_AST'] = $lang['en_US'];
}


?>