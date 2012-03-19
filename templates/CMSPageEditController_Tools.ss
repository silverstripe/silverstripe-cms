<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSPageEditController">
	<div class="cms-content-header cms-panel-header north">
		<h2><% _t('CMSPageEditController.Title','Pages') %></h2>
	</div>
	
	<div class="cms-panel-content center">
		<div class="cms-tree" data-url-tree="$Link(getsubtree)" data-hints="$SiteTreeHints">
			$SiteTreeAsUL
		</div>
	</div>

	<div class="cms-panel-content-collapsed">
		<h3 class="cms-panel-header">$SiteConfig.Title</h3>
	</div>

</div>

