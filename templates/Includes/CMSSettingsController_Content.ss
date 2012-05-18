<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<% with EditForm %>
				<h2>
				<% with Controller %>
					<% include CMSSectionIcon %>
					<% include CMSBreadcrumbs %>
				<% end_with %>
				</h2>
				<% if Fields.hasTabset %>
					<% with Fields.fieldByName('Root') %>
					<div class="cms-tabset cms-content-header-tabs ss-ui-tabs-nav">
						<ul>
						<% loop Tabs %>
							<li<% if extraClass %> class="$extraClass"<% end_if %>><a href="#$id">$Title</a></li>
						<% end_loop %>
						</ul>
					</div>
					<% end_with %>
				<% end_if %>
			<% end_with %>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content" data-layout-type="border">

		$EditForm

	</div>

</div>