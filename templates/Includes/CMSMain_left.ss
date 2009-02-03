<!-- <div class="title"><div style="background-image : url(cms/images/panels/MySite.png)">My Site</div></div> -->

	<div id="treepanes">
		<h2 id="heading_sitetree" class="selected">
			<img id="sitetree_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" style="display:none;" title="<% _t('OPENBOX','click to open this box') %>" />
			<img id="sitetree_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" title="<% _t('CLOSEBOX','click to close box') %>" />
			<% _t('SITECONTENT TITLE','Site Content and Structure',PR_HIGH) %>
		</h2>
		<div id="sitetree_holder">
			<div id="TreeTools">
				<ul id="TreeActions">
					<li class="action" id="addpage"><button<% if EditingLang %> disabled="disabled" class="disabled"<% end_if %>><% _t('CREATE','Create',PR_HIGH) %></button></li>
					<li class="action" id="search"><button><% _t('SEARCH','Search',PR_HIGH) %></button></li>
					<li class="action" id="batchactions"><button><% _t('BATCHACTIONS','Batch Actions',PR_HIGH) %></button></li>
					<!-- <li class="action" id="duplicate"><a href="#">Duplicate</a></li>
					Sam: this should be put into the Create area, I think, so we don't stuff up the layout -->
				</ul>
				<div style="clear:both;"></div>
				<% control AddPageOptionsForm %>
				<form class="actionparams" id="$FormName" style="display: none" action="$FormAction">
					<% control Fields %>
					$FieldHolder
					<% end_control %>
					<!--
					<div>
					<select name="Type">
						<% control PageTypes %>
						<option value="$ClassName">$AddAction</option>
						<% end_control %>
					</select>
					<input type="hidden" name="ParentID" />
					</div>
					-->
					<div>
					<input class="action" type="submit" value="<% _t('GO','Go') %>" />
					</div>
				</form>
				<% end_control %>
				<form class="actionparams" style="display: none" id="search_options" action="admin/filterSiteTree">
					<div>
					<input type="hidden" id="SiteTreeIsFiltered" value="0" />
					
					<div id="SearchBox">
						<input type="text" id="SiteTreeSearchTerm" name="SiteTreeSearchTerm" />
						<input type="submit" id="SiteTreeSearchButton" class="action" value="<% _t('SEARCH') %>" title="<% _t('SEARCHTITLE','Search through URL, Title, Menu Title, &amp; Content') %>" />
					</div>
					
					<div style="display:none" id="TextSiteTreeFilterDate" class="SearchCriteria"><% _t('EDITEDSINCE','Edited Since') %>:</div>
					<div style="display:none" id="InputSiteTreeFilterDate">$SiteTreeFilterDateField</div>
					<% control SiteTreeFilterOptions %>
						<div style="display:none" id="Text$Column" class="SearchCriteria">$Title:</div>
						<input style="display:none" id="Input$Column" name="$Column" class="SearchCriteria" />
					<% end_control %>
					<select id="SiteTreeFilterAddCriteria">
						<option><% _t('ADDSEARCHCRITERIA','Add Criteria...') %></option>
						<option value="SiteTreeFilterDate"><% _t('EDITEDSINCE','Edited Since') %></option>
						<% control SiteTreeFilterOptions %>
	        				<option value="$Column">$Title</option>
						<% end_control %>
					</select>
					</div>
				</form>
				<div id="batchactionsforms" style="display: none">
					$DeleteItemsForm
					$PublishItemsForm
					
				</div>
				<div id="SortItems">
						<input type="checkbox" id="sortitems" /> <label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
				</div>
				<div id="LangSelector_holder" <% if MultipleLanguages %><% else %> class="onelang"<% end_if %>>
					Language: $LangSelector
				</div>
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
		</div>		
		
		<!--
		<div id="search_holder" style="display:none">
			<h2>Search</h2>
			<div class="unitBody"></div>
		</div>
		-->

		<h2 id="heading_versions">
			<img id="versions_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" title="<% _t('OPENBOX') %>" />
			<img id="versions_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" style="display:none;" title="<% _t('CLOSEBOX') %>" /> 
			<% _t('PAGEVERSIONH','Page Version History') %>
		</h2>
		<div class="listpane" id="versions_holder" style="display:none">
			<p class="pane_actions" id="versions_actions">
				<input type="checkbox" id="versions_comparemode" /> <label for="versions_comparemode"><% _t('COMPAREMODE','Compare mode (click 2 below)') %></label>
				<br />

				<input type="checkbox" id="versions_showall" /> <label for="versions_showall"><% _t('SHOWUNPUB','Show unpublished versions') %></label>
			</p>
			
			<div class="unitBody">
			</div>
		</div>

		<h2 id="heading_reports">
			<img id="reports_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" title="<% _t('OPENBOX') %>" />
			<img id="reports_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" style="display:none;" title="<% _t('CLOSEBOX') %>" /> 
			<% _t('SITEREPORTS','Site Reports') %>
		</h2>
		<div class="listpane" id="reports_holder" style="display:none">
			<p id="ReportSelector_holder">$ReportSelector <input class="action" type="submit" id="report_select_go" value="<% _t('GO','Go') %>" /></p>
			<div class="unitBody">
			</div>
		</div>
	</div>
