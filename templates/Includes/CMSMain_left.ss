	<div id="treepanes">
		<h3>
			<a href="#"><% _t('SITECONTENT TITLE','Page Tree',PR_HIGH) %></a>
		</h3>
		<div id="sitetree_holder">
			<div id="sitetree_and_tools">
				<div id="TreeTools">
					 <% include CMSMain_TreeTools %>
				</div>
				<div id="sitetree_ul" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="jstree jstree-apple">
					$SiteTreeAsUL
				</div>
			</div>
			
			<div id="publication_key">
				<% _t('KEY','Key:') %>
				<ins style="cursor: help" title="<% _t('ADDEDNOTPUB','Added to the draft site and not published yet') %>"><% _t('NEW','new') %></ins>
				<del style="cursor: help" title="<% _t('DELETEDSTILLLIVE','Deleted from the draft site but still on the live site') %>"><% _t('DEL','deleted') %></del>
				<span style="cursor: help" title="<% _t('EDITEDNOTPUB','Edited on the draft site and not published yet') %>" class="modified"><% _t('CHANGED','changed') %></span>
				<span style="cursor: help" title="<% _t('NOTINMENU','Excluded from navigation menus') %>" class="notinmenu"><% _t('HIDDEN','hidden') %></span>
			</div>
		</div>	

		<h3>
			<a href="#"><% _t('PAGEVERSIONH','Page Version History') %></a>
		</h3>
		<div id="versions_holder">
			$VersionsForm
		</div>

		<h3>
			<a href="#"><% _t('SITEREPORTS','Site Reports') %></a>
		</h3>
		<div class="listpane" id="reports_holder">
			$SideReportsForm
			<div id="SideReportsHolder"></div>
		</div>
	</div>
