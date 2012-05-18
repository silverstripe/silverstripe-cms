<form $FormAttributes>
	
	<% if Message %>
	<p id="{$FormName}_error" class="message $MessageType">$Message</p>
	<% else %>
	<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>

	<fieldset>
		<% if Legend %><legend>$Legend</legend><% end_if %> 
		<% loop Fields %>
			$FieldHolder
		<% end_loop %>
		<div class="clear"><!-- --></div>
	</fieldset>

	<% if Actions %>
	<div class="Actions">
		<% loop Actions %>
			$Field
		<% end_loop %>
		<% if CurrentPage.LinkPreview %>
		<a href="$CurrentPage.LinkPreview" class="cms-preview-toggle-link ss-ui-button" data-icon="preview">
			<% _t('LeftAndMain.PreviewButton', 'Preview') %> &raquo;
		</a>
		<% end_if %>
	</div>
	<% end_if %>

</form>