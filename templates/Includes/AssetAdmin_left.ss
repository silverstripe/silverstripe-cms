<h2><% _t('FOLDERS','Folders') %></h2>
	<div id="treepanes" style="overflow-y: auto;">
			<ul id="TreeActions">
				<li class="action" id="addpage"><button><% _t('CREATE','Create') %></button></li>
				<li class="action" id="deletepage"><button><% _t('DELETE','Delete') %></button></li>
			</ul>
			<div style="clear:both;"></div>
			<form class="actionparams" id="addpage_options" style="display: none" action="admin/assets/addfolder">
				<div>
				<input type="hidden" name="ParentID" />
				<input class="action" type="submit" value="<% _t('GO','Go') %>" />
				</div>
			</form>
		
			<form class="actionparams" id="deletepage_options" style="display: none" action="admin/assets/deletefolder">
				<p><% _t('SELECTTODEL','Select the folders that you want to delete and then click the button below') %></p>
				<div>		
				<input type="hidden" name="csvIDs" />
				<input type="submit" value="<% _t('DELFOLDERS','Delete the selected folders') %>" class="action delete" />
				</div>
			</form>
		
			<form class="actionparams" id="sortitems_options" style="display: none">
				<p id="sortitems_message" style="margin: 0"><% _t('TOREORG','To reorganise your folders, drag them around as desired.') %></p>
			</form>
			<div class="checkboxAboveTree">
					<input type="checkbox" id="sortitems" /> <label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
			</div>
		
			$SiteTreeAsUL
	</div>