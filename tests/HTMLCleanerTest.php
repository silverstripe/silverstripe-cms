<?php
class HTMLCleanerTest extends SapphireTest {

	function testHTMLClean() {
		$cleaner = HTMLCleaner::inst();

		if ($cleaner instanceof TidyHTMLCleaner) {
			$testContent = array(
				"<html><body><h1>heading<h2>subheading</h3></body></html>"=>"<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2//EN\">\n<html>\n<head>\n<title></title>\n</head>\n<body>\n<h1>heading</h1>\n<h2>subheading</h2>\n</body>\n</html>",
				"<html><body><p>here is a para <b>bold <i>bold italic</b> bold?</i> normal?</body></html>"=>"<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2//EN\">\n<html>\n<head>\n<title></title>\n</head>\n<body>\n<p>here is a para <b>bold <i>bold italic</i> bold?</b> normal?</p>\n</body>\n</html>"
			);
		} else {
			$testContent = array(
				"<h1>heading<h2>subheading</h3>"=>"<h1>heading</h1><h2>subheading</h2>",
				"<p>here is a para <b>bold <i>bold italic</b> bold?</i> normal?"=>"<p>here is a para <b>bold <i>bold italic</i></b> bold? normal?</p>"
			);
		}

		//skip this test if no instance of either tidy of HTMLCleaner is available
		if ($cleaner) {
			foreach($testContent as $dirty => $pure) {
				$cleaned = $cleaner->cleanHTML($dirty);
				$this->assertEquals($pure, $cleaned, "HTML cleaned properly");
			}
		}
	}

}
?>