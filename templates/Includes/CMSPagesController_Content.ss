<div class="cms-content center ss-tabset" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<h2><% _t('CMSPagesController.Title','Edit & Organize') %></h2>
		
		<div class="cms-content-header-tabs">
			<ul>
				<li>
					<a href="#cms-content-treeview"><% _t('CMSPagesController.TreeView', 'Tree View') %></a>
				</li>
				<li>
					<a href="#cms-content-galleryview"><% _t('CMSPagesController.GalleryView', 'Gallery View') %></a>
				</li>
				<li>
					<a href="#cms-content-listview"><% _t('CMSPagesController.ListView', 'List View') %></a>
				</li>
			</ul>
		</div>

	</div>

	<div class="cms-content-tools west">
		
		<form class="actionparams" id="search_options" action="$Link(filterSiteTree)">
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
		</form>
		
	</div>

	<div class="cms-content-form center ui-widget-content">
		
		<div id="cms-content-treeview">
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)">
				$SiteTreeAsUL
			</div>
			
			<div id="TreeActions-batchactions">
				$BatchActionsForm
			</div>
		
			<% if CanOrganiseSitetree %> 
				<div class="checkboxAboveTree">
					<input type="checkbox" id="sortitems" />
					<label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
				</div>
			<% end_if %>
		</div>
		
		<div id="cms-content-listview">
			...
		</div>
		
		<div id="cms-content-galleryview">
			...
		</div>
		
	</div>
	
</div>