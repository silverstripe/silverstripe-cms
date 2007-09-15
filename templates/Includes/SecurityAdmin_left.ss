<h2>Security Groups</h2>
<div id="treepanes" style="overflow-y: auto;">
		<ul id="TreeActions">
			<li class="action" id="addgroup"><button>Create</button></li>
			<li class="action" id="deletegroup"><button>Delete...</button></li>
			<li class="action" id="sortitems"><button>Reorder...</button></li>
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