<?php

class ImprintStats extends ViewableData {
	protected static $imprintID;
	
	static function setID($id) {
		ImprintStats::$imprintID = $id;
	}
	
	function forTemplate() {
		$page = $this->CurrentPage();

		if($page){
			$description = str_replace(array("\n","\r"),"",$page->MetaDescription);
		
			$title = Convert::raw2js($page->Title);
		
			$level1 = $page->Level(1);
			$level2 = $page->Level(2);
			if($level1->URLSegment == 'modes') {
				$level1 = $level2;
				$level2 = $page->Level(3);
			}
			if($level2 && $level1->URLSegment == 'home') {
				$level1 = $level2;
				$level2 = $page->Level(3);
			}
	
			
			$section = $level1->Title;
			$service = $level2 ? $level2->Title : $section;
			
			$fullURL = $page->Link();
			
			$imprintID = ImprintStats::$imprintID;
		
			return "<!-- Start: Nexsys Imprint Code (Copyright Nexsys Development Ltd 2002-2005) -->
		<script src=\"cms/javascript/ImprintStats.js\" type=\"text/javascript\"></script>
		<script type=\"text/javascript\">
		//<![CDATA[	
		var NI_DESCRIPTION = \"$description\";
		var NI_SECTION = \"$section\";
		var NI_SERVICE = \"$service\";
		var NI_TRIGGER = \"$trigger\";
		var NI_AMOUNT = \"0\";
		var NI_ADCAMPAIGN = \"\";
		var NI_TITLE = \"$title\";
		var NI_URL = \"$fullURL\";
		var NI_BASKET_ADD = \"\";
		var NI_BASKET_REMOVE = \"\";
		var NI_PARAMETERS = \"\";
		
		if (typeof(NI_IW) != \"undefined\") ni_TrackHit(\"imprint1.nexsysdev.net\", $imprintID, NI_DESCRIPTION, NI_SECTION, NI_SERVICE, NI_TRIGGER, NI_AMOUNT, NI_ADCAMPAIGN, NI_TITLE, NI_URL, 1, NI_BASKET_ADD, NI_BASKET_REMOVE, NI_PARAMETERS);
		else document.write('<div style=\"position:absolute;width:1px;height:1px;overflow:hidden\"><img src=\"http://imprint1.nexsysdev.net/Hit.aspx?tv=1&sc=$imprintID&js=1\" width=\"1\" height=\"1\" style=\"border:0\" alt=\"Nexsys Imprint\"/></div>');
		//]]>
		</script>
		
		<noscript>
		<div style=\"position:absolute;width:1px;height:1px;overflow:hidden\"><a href=\"http://www.nexsysdev.com\"><img src=\"http://imprint1.nexsysdev.net/Hit.aspx?tv=1&sc=55075&lo=&st=&sv=&tr=&ac=&js=0\" width=\"1\" height=\"1\" alt=\"Nexsys Imprint\" /></a></div>
		</noscript>
		<!-- End: Nexsys Imprint Code -->";
		}
	}
}

?>