<div id="pages-controller-cms-content" class="has-panel cms-content flexbox-area-grow fill-height $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">
	<div class="fill-width fill-height flexbox-area-grow">
		$Tools

		<div class="fill-height flexbox-area-grow">
			<div class="cms-content-header north">
				<div class="cms-content-header-nav fill-width">
					<% include SilverStripe\\Admin\\CMSBreadcrumbs %>

					<div class="cms-content-header-tabs cms-tabset">
						<ul class="cms-tabset-nav-primary nav nav-tabs">
							<li class="nav-item content-treeview<% if $class == 'SilverStripe\\CMS\\Controllers\\CMSPageEditController' %> ui-tabs-active<% end_if %>">
								<a href="$LinkPageEdit" class="nav-link cms-panel-link" title="Form_EditForm" data-href="$LinkPageEdit">
									<% _t('CMSMain.TabContent', 'Content') %>
								</a>
							</li>
							<li class="nav-item content-listview<% if $class == 'SilverStripe\\CMS\\Controllers\\CMSPageSettingsController' %> ui-tabs-active<% end_if %>">
								<a href="$LinkPageSettings" class="nav-link cms-panel-link" title="Form_EditForm" data-href="$LinkPageSettings">
									<% _t('CMSMain.TabSettings', 'Settings') %>
								</a>
							</li>
							<li class="nav-item content-listview<% if $class == 'SilverStripe\\CMS\\Controllers\\CMSPageHistoryController' %> ui-tabs-active<% end_if %>">
								<a href="$LinkPageHistory" class="nav-link cms-panel-link" title="Form_EditForm" data-href="$LinkPageHistory">
									<% _t('CMSMain.TabHistory', 'History') %>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="flexbox-area-grow fill-width fill-height">
				$EditForm
			</div>
		</div>
	</div>
</div>
