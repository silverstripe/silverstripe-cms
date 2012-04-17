<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('AssetAdmin.ADDFILES', 'Add Files') %></h2>
			<div class="cms-content-header-tabs">
				<ul>
					<li>
						<a href="#cms-content-fromyourcomputer"><% _t('AssetAdmin.FROMYOURCOMPUTER', 'From your computer') %></a>
					</li>
					<li>
						<a href="#cms-content-fromtheinternet"><% _t('AssetAdmin.FROMTHEINTERNET', 'From the internet') %></a>
					</li>
				</ul>
			</div>
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