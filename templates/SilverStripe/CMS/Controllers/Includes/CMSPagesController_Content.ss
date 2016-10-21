<div id="pages-controller-cms-content" class="flexbox-area-grow fill-height cms-content $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info fill-width">
			<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
			<% include SilverStripe\\CMS\\Controllers\\CMSMain_ViewControls PJAXTarget='Content-PageList' %>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded flexbox-area-grow fill-height">
		$Tools
		$PageList
	</div>

</div>
