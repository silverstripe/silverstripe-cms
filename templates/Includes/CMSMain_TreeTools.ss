			<div id="TreeActions">
				
				<ul>
					<li>
						<a href="#TreeActions-create" id="addpage">
							<% _t('CREATE','Create',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#TreeActions-search" id="search">
							<% _t('SEARCH','Search',PR_HIGH) %>
						</a>
					</li>
					<li>
						<a href="#TreeActions-batchactions" id="batchactions">
							<% _t('BATCHACTIONS','Batch Actions',PR_HIGH) %>
						</a>
					</li>
				</ul>
			
				<div id="TreeActions-create">
					$AddPageOptionsForm
				</div>
			
				<div id="TreeActions-search">
					$SearchTreeForm
				</div>

				<div id="TreeActions-batchactions">
					$BatchActionsForm
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