<!-- <div class="title"><div style="background-image : url(cms/images/panels/MySite.png)">My Site</div></div> -->

	<div id="treepanes">
		<h2 id="heading_sitetree" class="selected">
			<img id="sitetree_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" style="display:none;" title="<% _t('OPENBOX','click to open this box') %>" />
			<img id="sitetree_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" title="<% _t('CLOSEBOX','click to close box') %>" />
			<% _t('SITECONTENT TITLE','Page Tree',PR_HIGH) %>
		</h2>
		<div id="sitetree_holder">
			<div id="TreeTools">
				<ul id="TreeActions">
					<li class="action" id="addpage"><button><% _t('CREATE','Create',PR_HIGH) %></button></li>
					<li class="action" id="search"><button><% _t('SEARCH','Search',PR_HIGH) %></button></li>
					<li class="action" id="batchactions"><button><% _t('BATCHACTIONS','Batch Actions',PR_HIGH) %></button></li>
				</ul>
				<div style="clear:both;"></div>
				<% control AddPageOptionsForm %>
				<form class="actionparams" id="$FormName" style="display: none" action="$FormAction">
					<% control Fields %>
					$FieldHolder
					<% end_control %>
					<div>
						<input class="action" type="submit" value="<% _t('GO','Go') %>" />
					</div>
				</form>
				<% end_control %>
				<form class="actionparams" style="display: none" id="search_options" action="admin/filterSiteTree">
				<div>
					<input type="hidden" id="SiteTreeIsFiltered" value="0" />
					
					<div id="SearchBox">
						<div class="SearchCriteria"><% _t('FILTERLABELTEXT', 'Text') %>:</div>
						<input type="text" id="SiteTreeSearchTerm" class='SearchCriteria' name="SiteTreeSearchTerm" />
					</div>
					
					<div id="ContainerSiteTreeFilterDate" class="SearchCriteriaContainer" style="display:none">
						<div id="TextSiteTreeFilterDate" class="SearchCriteria"><% _t('EDITEDSINCE','Edited Since') %>:</div>
						<div id="InputSiteTreeFilterDate">$SiteTreeFilterDateField</div>
					</div>
					<div id='ContainerSiteTreeFilterClassName' class='SearchCriteriaContainer' style="display:none">
						<div id="TextSiteTreeFilterClassName" class="SearchCriteria"><% _t('FILTERLABELPAGETYPE', 'Page type') %>: </div>
						<div id="InputSiteTreeFilterClassName">$SiteTreeFilterPageTypeField</div>
					</div>

					<% control SiteTreeFilterOptions %>
						<div id="Container$Column" class="SearchCriteriaContainer" style="display:none">
							<div id="Text$Column" class="SearchCriteria">$Title:</div>
							<input id="Input$Column" name="$Column" class="SearchCriteria" />
						</div>
					<% end_control %>
					
					<div id='SearchControls'>
						<select id="SiteTreeFilterAddCriteria">
							<option value=""><% _t('ADDSEARCHCRITERIA','Add Criteria') %></option>
							<option value="SiteTreeFilterDate"><% _t('EDITEDSINCE','Edited Since') %></option>
							<option value="SiteTreeFilterClassName"><% _t('FILTERLABELPAGETYPE', 'Page type') %></option>
							<% control SiteTreeFilterOptions %>
		        				<option value="$Column">$Title</option>
							<% end_control %>
						</select>
						<div id="searchIndicator">&nbsp;</div>
						<input type="submit" id="SiteTreeSearchClearButton" class="action" value="<% _t('CLEAR','Clear') %>" title="<% _t('CLEARTITLE','Clear the search and view all items') %>" />
						<input type="submit" id="SiteTreeSearchButton" class="action" value="<% _t('SEARCH','Search') %>" title="<% _t('SEARCHTITLE','Search through URL, Title, Menu Title, &amp; Content') %>" />
					</div>
				</div>
				</form>
			
			<div id="batchactionsforms" style="display: none">
				<form class="actionparams" style="border:0" id="batchactions_options" action="">
					$BatchActionParameters
				
					<p><% _t('SELECTPAGESACTIONS','Select the pages that you want to change &amp; then click an action:') %></p>

					<input type="hidden" name="csvIDs" />
					<input type="hidden" name="SecurityID" value="$SecurityID" />
					<div id="actionParams"></div>
					<div>
						<select id="choose_batch_action">
							<% control BatchActionList %>
							<option value="$Link" class="{doingText:'$DoingText'}">$Title</option>
							<% end_control %>
						</select>
						<input id="batchactions_go" type="submit" class="action" value="Go" />
					</div>
				</form>
			</div>
			<div class="checkboxAboveTree noBottomBorder">
				<% _t('SHOWITEMS', 'Show:') %> <select id="siteTreeFilterList">
					<% control SiteTreeFilters %>
					<option value="$ClassName">$Title</option>
					<% end_control %>
				</select> <img id="siteTreeFilterActionIndicator" style="display:none" src="cms/images/network-save.gif">
			</div>
			<% if CanOrganiseSitetree %>
			<div class="checkboxAboveTree">
				<img id="checkboxActionIndicator" src="cms/images/network-save.gif">
				<div>
					<input type="checkbox" id="sortitems" />
					<label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
				</div>
			</div>
			<% end_if %>
			<% if IsTranslatableEnabled %>
			<div id="LangSelector_holder">
				Language: $LangSelector
			</div>
			<% end_if %>

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

		<h2 id="heading_reports">
			<img id="reports_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" title="<% _t('OPENBOX') %>" />
			<img id="reports_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" style="display:none;" title="<% _t('CLOSEBOX') %>" /> 
			<% _t('SITEREPORTS','Site Reports') %>
		</h2>
		$ReportFormParameters
		<div class="listpane" id="reports_holder" style="display:none">
			<p id="ReportSelector_holder">
				$ReportSelector
				<input class="action" type="submit" id="report_select_go" onclick="$('reports_holder').showreport();" value="<% _t('GO','Go') %>" />
			</p>
			<div class="unitBody"></div>
		</div>
	</div>
