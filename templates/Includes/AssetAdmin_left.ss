<script>
	_TREE_ICONS = {};
	_TREE_ICONS['Folder'] = {
			fileIcon: 'jsparty/tree/images/page-closedfolder.gif',
			openFolderIcon: 'jsparty/tree/images/page-openfolder.gif',
			closedFolderIcon: 'jsparty/tree/images/page-closedfolder.gif'
	};
</script>

<div class="title"><div style="background-image : url(cms/images/panels/MySite.png)">Files &amp; Images</div></div>
	<div id="treepanes">
		<div id="sitetree_holder">
			<ul id="TreeActions">
				<li class="action" id="addpage"><a href="admin/addpage">Create</a></li>
				<li class="action" id="deletepage"><a href="admin/deletepage">Delete</a></li>
				<li class="action" id="sortitems"><a href="#">Reorganise</a></li>
			</ul>
			<div style="clear:both;"></div>
			<form class="actionparams" id="addpage_options" style="display: none" action="admin/assets/addfolder">
				<div>
				<input type="hidden" name="ParentID" />
				<input class="action" type="submit" value="Go" />
				</div>
			</form>
		
			<form class="actionparams" id="deletepage_options" style="display: none" action="admin/assets/deletefolder">
				<p>Select the folders that you want to delete and then click the button below</p>
				<div>		
				<input type="hidden" name="csvIDs" />
				<input type="submit" value="Delete the selected folders" />
				</div>
			</form>
		
			<form class="actionparams" id="sortitems_options" style="display: none">
				<p id="sortitems_message" style="margin: 0">To reorganise your folders, drag them around as desired.</p>
			</form>
		
			$SiteTreeAsUL
		</div>
	</div>