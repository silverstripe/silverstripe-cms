<?php
class CMSMainSearchFormTest extends FunctionalTest {
	
	static $fixture_file = '../controller/CMSMainTest.yml';
	
	protected $autoFollowRedirection = false;
	
	function testTitleFilter() {
		$this->session()->inst_set('loggedInAs', $this->idFromFixture('Member', 'admin'));

		$response = $this->get(
			'admin/pages/SearchForm/?' .
			http_build_query(array(
				'q' => array(
					'Title' => 'Page 10',
					'FilterClass' => 'CMSSiteTreeFilter_Search',
				),
				'action_doSearch' => true
			))
		);
		
		$titles = $this->getPageTitles();
		$this->assertEquals(count($titles), 1);
		// For some reason the title gets split into two lines
		
		$this->assertContains('Page 1', $titles[0]);
	}
	
	protected function getPageTitles() {
		$titles = array();
		$links = $this->cssParser()->getBySelector('li.class-Page a');
		if($links) foreach($links as $link) {
			$titles[] = preg_replace('/\n/', ' ', $link->asXML());
		}
		return $titles;
	}
}
