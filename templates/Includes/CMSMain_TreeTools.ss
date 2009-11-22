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
					$AddForm
				</div>
			
				<div id="TreeActions-search">
					$SearchTreeForm
				</div>

				<div id="TreeActions-batchactions">
					$BatchActionsForm
				</div>
			
			</div>
			
			<% if CanOrganiseSitetree %> 
				<div class="checkboxAboveTree">
					<img id="checkboxActionIndicator" src="cms/images/network-save.gif" />
					<div>
						<input type="checkbox" id="sortitems" />
						<label for="sortitems"><% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %></label>
					</div>
				</div>
			<% end_if %> 

			<% if IsTranslatableEnabled %>
			<div id="LangSelector_holder">
				$LangForm
			</div>
			<% end_if %>