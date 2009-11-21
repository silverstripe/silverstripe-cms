<div id="treepanes">
	<h3>
		<a href="#"><% _t('SECGROUPS','Security Groups') %></a>
	</h3>
	
	<div>
		<div id="TreeActions">

			<ul>
				<li>
					<a href="#TreeActions-create">
						<% _t('CREATE','Create',PR_HIGH) %>
					</a>
				</li>
				<li>
					<a href="#TreeActions-delete">
						<% _t('DELETE','Delete',PR_HIGH) %>
					</a>
				</li>
			</ul>
			
			<div id="TreeActions-create">
				<form class="actionparams" id="addgroup_options" action="admin/security/addgroup">
					<input type="hidden" name="ParentID" />
					<input class="action" type="submit" value="<% _t('GO','Go') %>" />
				</form>
			</div>
			
			<div id="TreeActions-delete">
				<form class="actionparams" id="deletegroup_options" style="display: none" action="admin/security/deleteitems">
					<p><% _t('SELECT','Select the pages that you want to delete and then click the button below') %></p>

					<input type="hidden" name="csvIDs" />
					<input type="submit" value="<% _t('DELGROUPS','Delete the selected groups') %>" class="action delete" />
				</form>
			</div>
			
		</div>
		
		<div class="checkboxAboveTree">
				<input type="checkbox" id="sortitems" /> <label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
		</div>

		$SiteTreeAsUL
		
	</div>
	
</div>