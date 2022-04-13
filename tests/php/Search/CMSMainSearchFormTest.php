<?php

namespace SilverStripe\CMS\Tests\Search;

use SilverStripe\CMS\Controllers\CMSSiteTreeFilter_Search;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;

class CMSMainSearchFormTest extends FunctionalTest
{
    protected static $fixture_file = '../Controllers/CMSMainTest.yml';

    public function testTitleFilter()
    {
        $this->logInAs('admin');

        $this->get(
            'admin/pages/?' .
            http_build_query([
                'q' => [
                    'Term' => 'Page 10',
                    'FilterClass' => CMSSiteTreeFilter_Search::class,
                ]
            ])
        );

        $titles = $this->getPageTitles();
        $this->assertEquals(count($titles ?? []), 1);
        // For some reason the title gets split into two lines

        $this->assertStringContainsString('Page 1', $titles[0]);
    }

    protected function getPageTitles()
    {
        $titles = [];
        $links = $this->cssParser()->getBySelector('.col-getTreeTitle span.item');
        if ($links) {
            foreach ($links as $link) {
                $titles[] = preg_replace('/\n/', ' ', $link->asXML() ?? '');
            }
        }
        return $titles;
    }
}
