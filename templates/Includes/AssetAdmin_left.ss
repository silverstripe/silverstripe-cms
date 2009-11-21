	<div id="treepanes">
		<h3>
			<a href="#"><% _t('FOLDERS','Folders') %></a>
		</h3>
		
		<div>
			<div id="TreeActions">

				<ul>
					<li>
						<a href="#TreeActions-create" id="TreeActions-create-btn">
							<% _t('CREATE','Create',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#TreeActions-delete" id="TreeActions-delete-btn">
							<% _t('DELETE','Delete',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#" title="<% _t('FILESYSTEMSYNC_DESC', 'SilverStripe maintains its own database of the files &amp; images stored in your assets/ folder.  Click this button to update that database, if files are added to the assets/ folder from outside SilverStripe, for example, if you have uploaded files via FTP.') %>">
							<% _t('FILESYSTEMSYNC','Look for new files') %>
						</a>
					</li>
				</ul>

				<div id="TreeActions-create">
					<form class="actionparams" id="addpage_options" action="admin/assets/addfolder">
						<div>
						<input type="hidden" name="ParentID" />
						<input class="action" type="submit" value="<% _t('GO','Go') %>" />
						</div>
					</form>
				</div>

				<div id="TreeActions-delete">
					<form class="actionparams" id="deletepage_options" style="display: none" action="admin/assets/deletefolder">
						<p><% _t('SELECTTODEL','Select the folders that you want to delete and then click the button below') %></p>
						<div>		
							<input type="hidden" name="csvIDs" />
							<input type="submit" value="<% _t('DELFOLDERS','Delete the selected folders') %>" class="action delete" />
						</div>
					</form>
				</div>

			</div>
			
			<div class="checkboxAboveTree">
				<input type="checkbox" id="sortitems" />
				<label for="sortitems">
					<% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %>
				</label>
			</div>

			<div id="sitetree_ul">
				$SiteTreeAsUL
			</div>
			
		</div>

	</div>