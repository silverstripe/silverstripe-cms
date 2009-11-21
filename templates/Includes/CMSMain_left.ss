	<div id="treepanes">
		<h3>
			<a href="#"><% _t('SITECONTENT TITLE','Site Content and Structure',PR_HIGH) %></a>
		</h3>
		<div id="sitetree_holder">
			<div id="sitetree_and_tools">
				<div id="TreeTools">
					 <% include CMSMain_TreeTools %>
				</div>
				<div id="sitetree_ul">
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
			<p class="pane_actions" id="versions_actions">
				
				<span class="versionChoice">
					<input type="checkbox" id="versions_comparemode" /> <label for="versions_comparemode"><% _t('COMPAREMODE','Compare mode (click 2 below)') %></label>
				</span>
				<span class="versionChoice">
					<input type="checkbox" id="versions_showall" /> <label for="versions_showall"><% _t('SHOWUNPUB','Show unpublished versions') %></label>
				</span>
			
			</p>
			
			<div class="unitBody">
			</div>
		</div>

		<h3>
			<a href="#"><% _t('SITEREPORTS','Site Reports') %></a>
		</h3>
		<div class="listpane" id="reports_holder">
			<p id="ReportSelector_holder">$ReportSelector <input class="action" type="submit" id="report_select_go" value="<% _t('GO','Go') %>" /></p>
			<div class="unitBody">
			</div>
		</div>
	</div>
