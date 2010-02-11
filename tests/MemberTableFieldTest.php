<?php
class MemberTableFieldTest extends SapphireTest {
	static $fixture_file = 'cms/tests/MemberTableFieldTest.yml';
	
	function testLimitsToMembersInGroup() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members",
			$group1
		);
		$members = $tf->sourceItems();
		
		$this->assertContains($member1->ID, $members->column('ID'),
			'Members in the associated group are listed'
		);
		$this->assertContains($member2->ID, $members->column('ID'),
			'Members in children groups are listed as well'
		);
		$this->assertNotContains($member3->ID, $members->column('ID'),
			'Members in other groups are filtered out'
		);
	}
	
	function testShowsAllMembersWithoutGroupParameter() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members"
			// no group assignment
		);
		$members = $tf->sourceItems();
		
		$this->assertContains($member1->ID, $members->column('ID'),
			'Members in the associated group are listed'
		);
		$this->assertContains($member2->ID, $members->column('ID'),
			'Members in children groups are listed as well'
		);
		$this->assertContains($member3->ID, $members->column('ID'),
			'Members in other groups are listed'
		);
	}
	
	function testDeleteWithGroupOnlyDeletesRelation() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members",
			$group1
		);
		$tfItem = new MemberTableField_ItemRequest($tf, $member1->ID);
		$tfItem->delete();

		$group1->flushCache();
		
		$this->assertNotContains($member1->ID, $group1->Members()->column('ID'),
			'Member relation to group is removed'
		);
		$this->assertType(
			'DataObject',
			DataObject::get_by_id('Member', $member1->ID),
			'Member record still exists'
		);
	}
	
	function testDeleteWithoutGroupDeletesFromDatabase() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member1ID = $member1->ID;
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members"
			// no group assignment
		);
		$tfItem = new MemberTableField_ItemRequest($tf, $member1->ID);
		$tfItem->delete();

		$group1->flushCache();
		
		$this->assertNotContains($member1->ID, $group1->Members()->column('ID'),
			'Member relation to group is removed'
		);
		DataObject::flush_and_destroy_cache();
		$this->assertFalse(
			DataObject::get_by_id('Member', $member1ID),
			'Member record is removed from database'
		);
	}
}