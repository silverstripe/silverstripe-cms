<form $FormAttributes>

	
	<p style="display: none;" class="message " id="Form_EditForm_error"/>
	

		<div id="ScrollPanel">
		<fieldset>
			$FieldMap.ReportTitle.FieldHolder
			$FieldMap.ReportDescription.FieldHolder
		
			<!-- Filters -->
			
			<% if FieldMap.Filters.Children %>
			<div style="border-bottom: 1px #AAA solid"><b><% _t('ReportAdminForm.FILTERBY', 'Filter by') %></b></div>
			
			<% control FieldMap.Filters %>
			<% control Children %>
			<div style="float: left; margin: 5px 10px 10px 0; height: 35px">
				<label for="$ID" style="font-weight: bold; display: block">$Title</label>
				$Field
			</div>
			<% end_control %>
			<% end_control %>
			
			<div id="action_updatereport" style="float: left; margin: 1px 10px 10px 0">
			<br />
			<% if FieldMap.action_updatereport %>
			$FieldMap.action_updatereport.Field
			<% end_if %>
			</div>
			
			<div style="clear: both">&nbsp;</div>
			<% end_if %>
			
			$FieldMap.ReportContent.FieldHolder
			
			<% control HiddenFields %>$Field<% end_control %>
			
			</fieldset>
		</div>
			
		
		<div class="clear"><!-- --></div>
</form>