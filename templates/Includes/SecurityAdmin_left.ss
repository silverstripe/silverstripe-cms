<h2><% _t('SECGROUPS','Security Groups') %></h2>

<div id="treepanes" style="overflow-y: auto;">
	<ul id="TreeActions">
		<li class="action" id="addgroup"><button><% _t('CREATE','Create') %></button></li>
		<li class="action" id="deletegroup"><button><% _t('DEL','Delete') %></button></li>
	</ul>
	<div style="clear:both;"></div>
	<form class="actionparams" id="addgroup_options" style="display: none" action="admin/security/addgroup">
		<input type="hidden" name="ParentID" />
		<input type="hidden" name="SecurityID" value="$SecurityID" />
		<input class="action" type="submit" value="<% _t('GO','Go') %>" />
	</form>
	
	<form class="actionparams" id="deletegroup_options" style="display: none" action="admin/security/deleteitems">
		<p><% _t('SELECT','Select the pages that you want to delete and then click the button below') %></p>
		
		<input type="hidden" name="csvIDs" />
		<input type="hidden" name="SecurityID" value="$SecurityID" />
		<input type="submit" value="<% _t('DELGROUPS','Delete the selected groups') %>" class="action delete" />
	</form>
	
	<form class="actionparams" id="sortitems_options" style="display: none" action="">
		<p id="sortitems_message" style="margin: 0"><% _t('TOREORG','To reorganise your site, drag the pages around as desired.') %></p>
	</form>

	<div class="checkboxAboveTree">
			<input type="checkbox" id="sortitems" /> <label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
	</div>
	
	$SiteTreeAsUL
</div>