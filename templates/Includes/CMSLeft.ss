	<h1><% _t('SITECONT','Site Content') %></h1>
	
	<ul id="TreeActions">
		<li class="action" id="addpage"><a href="admin/addpage"><% _t('NEWPAGE','New Page...') %></a></li>
		<li class="action" id="deletepage"><a href="admin/deletepage"><% _t('DELPAGE','Delete Pages...') %></a></li>
		<li class="spacer"></li>
	</ul>
	
	<form class="actionparams" id="addpage_options" style="display: none" action="admin/addpage">
		<select name="Type">
			<% control PageTypes %>
			<option value="$ClassName">$AddAction</option>
			<% end_control %>
		</select>
		<input type="hidden" name="ParentID" />
		<input class="action" type="submit" value="<% _t('GO','Go') %>" />
	</form>

	<form class="actionparams" id="deletepage_options" style="display: none" action="admin/deletepages">
		<p><% _t('SELECTPAGESDEL','Select the pages that you want to delete and then click the button below') %></p>
		
		<input type="hidden" name="csvIDs" />
		<input type="submit" value="<% _t('DELPAGES','Delete the selected pages') %>" />
	</form>
	
	<div id="sitetree_holder" style="overflow:auto">
	$SiteTreeAsUL
	</div>