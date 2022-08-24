<?php

namespace SilverStripe\CMS\Tests\GraphQL;

use SilverStripe\AssetAdmin\Tests\GraphQL\FakeResolveInfo;
use SilverStripe\CMS\GraphQL\LinkablePlugin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;

class LinkablePluginTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Schema::class)) {
            $this->markTestSkipped('GraphQL 4 test ' . __CLASS__ . ' skipped');
        }
    }

    /**
     * @param bool $list
     * @dataProvider provideApply
     */
    public function testApply(bool $list)
    {
        $query = new ModelQuery(
            new DataObjectModel(SiteTree::class, new SchemaConfig()),
            'testQuery'
        );
        $query->setType($list ? '[SiteTree]' : 'SiteTree');

        $plugin = new LinkablePlugin();
        $plugin->apply($query, new Schema('test'));
        $args = $query->getArgs();
        $field = $list ? 'links' : 'link';
        $this->assertArrayHasKey($field, $args);
        $this->assertEquals($list ? '[String]' : 'String', $args[$field]->getType());
    }

    public function testResolver()
    {
        $page = new SiteTree([
            'Title' => 'Test page',
            'URLSegment' => 'test-page',
            'ParentID' => 0,
        ]);
        $page->write();
        $page->publishRecursive();

        $page = new SiteTree([
            'Title' => 'Other test page',
            'URLSegment' => 'other-test-page',
            'ParentID' => 0,
        ]);

        $page->write();
        $page->publishRecursive();

        $result = LinkablePlugin::applyLinkFilter('test', ['link' => 'test-page'], [], new FakeResolveInfo());
        $this->assertTrue($result->exists());
        $this->assertEquals('Test page', $result->first()->Title);

        $result = LinkablePlugin::applyLinkFilter('test', ['links' => ['test-page']], [], new FakeResolveInfo());
        $this->assertTrue($result->exists());
        $this->assertEquals('Test page', $result->first()->Title);

        $result = LinkablePlugin::applyLinkFilter(
            'test',
            ['links' => ['test-page', 'other-test-page']],
            [],
            new FakeResolveInfo()
        );
        $this->assertTrue($result->exists());
        $this->assertCount(2, $result);
        $titles = $result->column('Title');
        $this->assertTrue(in_array('Test page', $titles ?? []));
        $this->assertTrue(in_array('Other test page', $titles ?? []));

        $result = LinkablePlugin::applyLinkFilter(
            'test',
            ['links' => ['test-page', 'fail-page']],
            [],
            new FakeResolveInfo()
        );
        $this->assertTrue($result->exists());
        $this->assertCount(1, $result);
        $this->assertEquals('Test page', $result->first()->Title);

        $result = LinkablePlugin::applyLinkFilter('test', ['link' => 'fail-page'], [], new FakeResolveInfo());
        $this->assertFalse($result->exists());

        $result = LinkablePlugin::applyLinkFilter('test', ['links' => ['fail-page']], [], new FakeResolveInfo());
        $this->assertFalse($result->exists());

        $result = LinkablePlugin::applyLinkFilter('test', ['notAnArg' => 'fail'], [], new FakeResolveInfo());
        $this->assertEquals('test', $result);
    }


    public function provideApply()
    {
        return [
            [true],
            [false],
        ];
    }
}
