<div class="cms-preview fill-height flexbox-area-grow" data-layout-type="border">
	<div class="panel flexbox-area-grow fill-height">
		<div class="preview-note">
            <div class="icon font-icon-monitor display-1"></div>
            <%t SilverStripe\CMS\Controllers\CMSPageHistoryController.NO_PREVIEW 'No preview available' %>
        </div>
		<div class="preview__device">
			<div class="preview-device-outer">
				<div class="preview-device-inner">
					<iframe src="about:blank" class="center" name="cms-preview-iframe"></iframe>
				</div>
			</div>
		</div>
	</div>
	<div class="toolbar toolbar--south cms-content-controls cms-preview-controls"></div>
</div>
