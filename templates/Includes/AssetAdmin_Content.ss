<div class="cms-content center ss-tabset $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('AssetAdmin.Title', 'Find &amp; Organize') %></h2>
			<div class="cms-content-header-tabs">
				<ul>
          <% loop EditForm.FieldMap.Root.Children %>
					<li>
						<a href="#cms-content-$Name">$Title</a>
					</li>
  				<% end_loop %>
				</ul>
			</div>
		</div>
	</div>


	<div class="cms-content-tools cms-panel west cms-panel-layout" data-expandOnClick="true" data-layout="{type: 'border'}">
		<div class="cms-panel-content center">
			<h3 class="cms-panel-header north"></h3>
			
			<div class="cms-content-tools-actions ui-widget-content">
				$AddForm
			</div>
			<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)">
				$SiteTreeAsUL
			</div>
		</div>
		
	</div>
	
	<% with EditForm %>
	<form $FormAttributes>
	<div class="cms-content-fields">
	  <% loop FieldMap.Root.Children %>
		<div id="cms-content-$Name">
			<fieldset>
			<% if Tabs %>
			$FieldHolder
			<% else %>
			<% loop Fields %>
			$FieldHolder
			<% end_loop %>
			<% end_if %>
			</fieldset>
		</div>
		<% end_loop %>
	</div>
	</form>
	<% end_with %>
	
</div>