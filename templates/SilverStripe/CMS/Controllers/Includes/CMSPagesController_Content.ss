<div id="pages-controller-cms-content" class="flexbox-area-grow fill-height cms-content $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north vertical-align-items">
		<div class="cms-content-header-info fill-width vertical-align-items">
			<% include SilverStripe\\Admin\\CMSBreadcrumbs %>
			<% include SilverStripe\\CMS\\Controllers\\CMSMain_Filter %>
		</div>
	</div>

	<div class="flexbox-area-grow fill-height cms-content-fields ui-widget-content cms-panel-padded">
		$Tools
		$PageList
	</div>
</div>
