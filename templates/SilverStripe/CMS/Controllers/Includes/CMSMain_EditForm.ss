<form $FormAttributes data-layout-type="border">

	<div class="panel panel--padded panel--scrollable flexbox-area-grow cms-content-fields ">
		<% if $Message %>
		<p id="{$FormName}_error" class="alert $AlertType">$Message</p>
		<% else %>
		<p id="{$FormName}_error" class="alert $AlertType" style="display: none"></p>
		<% end_if %>

		<fieldset>
			<% if $Legend %><legend>$Legend</legend><% end_if %>
			<% loop $Fields %>
				$FieldHolder
			<% end_loop %>
			<div class="clear"><!-- --></div>
		</fieldset>
	</div>

	<div class="toolbar--south cms-content-actions cms-content-controls south">
		<% if $Actions %>
		<div class="btn-toolbar">
			<% loop $Actions %>
				$FieldHolder
			<% end_loop %>
				<% if $Controller.LinkPreview %>
			<a href="$Controller.LinkPreview" target="_cmsPreview" class="cms-preview-toggle-link ss-ui-button" data-icon="preview">
				<%t SilverStripe\Admin\LeftAndMain.PreviewButton 'Preview' %> &raquo;
			</a>
			<% end_if %>

			<% include SilverStripe\\Admin\\LeftAndMain_ViewModeSelector SelectID="preview-mode-dropdown-in-content", ExtraClass='' %>
		</div>
		<% end_if %>
	</div>
</form>
