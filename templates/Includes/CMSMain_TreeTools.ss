			<div id="TreeActions">
				
				<ul>
					<li>
						<a href="#TreeActions-create">
							<% _t('CREATE','Create',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#TreeActions-search">
							<% _t('SEARCH','Search',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#TreeActions-batchactions">
							<% _t('BATCHACTIONS','Batch Actions',PR_HIGH) %>
						</a>
					</li>
				</ul>
			
				<div id="TreeActions-create">
					<% control AddPageOptionsForm %>
					<form class="actionparams" id="$FormName" action="$FormAction">
						<% control Fields %>
						$FieldHolder
						<% end_control %>
						<div>
							<input class="action" type="submit" value="<% _t('GO','Go') %>" />
						</div>
					</form>
					<% end_control %>
				</div>
			
				<div id="TreeActions-search">
					<form class="actionparams" id="search_options" action="admin/filterSiteTree">
					<div>
						<input type="hidden" id="SiteTreeIsFiltered" value="0" />
						<div id="SearchBox">
							<div class="SearchCriteria">Text:</div>
							<input type="text" id="SiteTreeSearchTerm" class='SearchCriteria' name="SiteTreeSearchTerm" />
						</div>
						<div id="ContainerSiteTreeFilterDate" class="SearchCriteriaContainer" style="display:none">
							<div id="TextSiteTreeFilterDate" class="SearchCriteria"><% _t('EDITEDSINCE','Edited Since') %>:</div>
							<div id="InputSiteTreeFilterDate">$SiteTreeFilterDateField</div>
						</div>
						<div id='ContainerSiteTreeFilterClassName' class='SearchCriteriaContainer' style="display:none">
							<div id="TextSiteTreeFilterClassName" class="SearchCriteria">Page type: </div>
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
								<option value="SiteTreeFilterClassName">Page type</option>
								<% control SiteTreeFilterOptions %>
									<option value="$Column">$Title</option>
								<% end_control %>
							</select>
							<div id="searchIndicator">&nbsp;</div>
							<input type="submit" id="SiteTreeSearchClearButton" class="action" value="<% _t('CLEAR') %>" title="<% _t('CLEARTITLE','Clear the search and view all items') %>" />
							<input type="submit" id="SiteTreeSearchButton" class="action" value="<% _t('SEARCH') %>" title="<% _t('SEARCHTITLE','Search through URL, Title, Menu Title, &amp; Content') %>" />
						</div>
					</div>
					</form>
				</div>

				<div id="TreeActions-batchactions">
					<form class="actionparams" style="border:0" id="batchactions_options" action="">
						<p><% _t('SELECTPAGESACTIONS','Select the pages that you want to change &amp; then click an action:') %></p>

						<input type="hidden" name="csvIDs" />

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
			
			</div>

			<div class="checkboxAboveTree" style="border-bottom:none">
				Show: <select id="siteTreeFilterList">
					<% control SiteTreeFilters %>
					<option value="$ClassName">$Title</option>
					<% end_control %>
				</select> <img id="siteTreeFilterActionIndicator" style="display:none" src="cms/images/network-save.gif">
			</div>
			
			<div class="checkboxAboveTree">
				<img id="checkboxActionIndicator" src="cms/images/network-save.gif">
				<div>
					<input type="checkbox" id="sortitems" />
					<label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
				</div>
			</div>

			<% if IsTranslatableEnabled %>
			<div id="LangSelector_holder">
				Language: $LangSelector
			</div>
			<% end_if %>