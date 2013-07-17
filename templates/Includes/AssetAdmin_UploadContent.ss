<div id="assetadmin-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<h2><% _t('AssetAdmin.ADDFILES', 'Add Files') %></h2>
		</div>
		<div class="cms-content-header-tabs">
			<ul class="cms-tabset-nav-primary">
				<li>
					<a href="#cms-content-fromyourcomputer"><% _t('AssetAdmin.FROMYOURCOMPUTER', 'From your computer') %></a>
				</li>
				<li>
					<a href="#cms-content-fromtheinternet"><% _t('AssetAdmin.FROMTHEINTERNET', 'From the internet') %></a>
				</li>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center">
		<div id="cms-content-fromyourcomputer">
			$UploadForm
		</div>
		<div id="cms-content-fromtheinternet">
			<i>Not implemented yet</i>
		</div>
	</div>

</div>