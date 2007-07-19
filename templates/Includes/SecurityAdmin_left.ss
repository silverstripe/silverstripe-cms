<div class="title"><div>Security Groups</div></div>
<div id="treepanes">
	<div id="sitetree_holder">
		<ul id="TreeActions">
			<li class="action" id="addgroup"><a href="admin/security/addgroup">Create</a></li>
			<li class="action" id="deletegroup"><a href="admin/security/deletegroup">Delete</a></li>
			<li class="action" id="sortitems"><a href="#">Reorganise</a></li>
		</ul>
		<div style="clear:both;"></div>
		<form class="actionparams" id="addgroup_options" style="display: none" action="admin/security/addgroup">
			<input type="hidden" name="ParentID" />
			<input class="action" type="submit" value="Go" />
		</form>
		
		<form class="actionparams" id="deletegroup_options" style="display: none" action="admin/security/deleteitems">
			<p>Select the pages that you want to delete and then click the button below</p>
			
			<input type="hidden" name="csvIDs" />
			<input type="submit" value="Delete the selected groups" />
		</form>
		
		<form class="actionparams" id="sortitems_options" style="display: none" action="">
			<p id="sortitems_message" style="margin: 0">To reorganise your site, drag the pages around as desired.</p>
		</form>
		
		$SiteTreeAsUL
	</div>
</div>