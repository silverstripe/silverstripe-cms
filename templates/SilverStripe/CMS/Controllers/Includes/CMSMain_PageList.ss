<div class="flexbox-area-grow cms-content-view" data-pjax-fragment="Content-PageList">
	<% if $ViewState == 'listview' %>
		<% include SilverStripe\\CMS\\Controllers\\CMSMain_ListView %>
	<% else %>
		<% include SilverStripe\\CMS\\Controllers\\CMSMain_TreeView %>
	<% end_if %>
</div>
