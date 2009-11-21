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