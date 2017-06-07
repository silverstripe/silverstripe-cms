<div class="cms-mobile-menu-toggle-wrapper"></div>
<div id="pages-controller-cms-content" class="flexbox-area-grow fill-height cms-content $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info fill-width">
			<div id="cms-mobile-menu-toggle-wrapper"></div>
			<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
			<% include SilverStripe\\CMS\\Controllers\\CMSMain_ViewControls PJAXTarget='Content-PageList' %>
		</div>
	</div>

	<div class="flexbox-area-grow fill-height cms-content-fields ui-widget-content cms-panel-padded">
		$Tools
		$PageList
	</div>
</div>
