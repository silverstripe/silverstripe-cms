<div class="cms-content-view fill-height flexbox-display" data-pjax-fragment="Content-PageList">
	<% if $ViewState == 'listview' %>
		<% include SilverStripe\\CMS\\Controllers\\CMSMain_ListView %>
	<% else %>
		<% include SilverStripe\\CMS\\Controllers\\CMSMain_TreeView %>
	<% end_if %>
</div>
