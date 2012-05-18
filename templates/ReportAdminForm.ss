<form $FormAttributes>

	
	<p style="display: none;" class="message " id="Form_EditForm_error"/>
	

		<div id="ScrollPanel">
		<fieldset>
			$FieldMap.ReportTitle.FieldHolder
			$FieldMap.ReportDescription.FieldHolder
		
			<% if FieldMap.Filters.Children %>
				<h4><% _t('ReportAdminForm.FILTERBY', 'Filter by') %></h4>
			
				<div class="filters">
					<% loop FieldMap.Filters %>
						<% loop Children %>
							$FieldHolder
						<% end_loop %>
					<% end_loop %>
				</div>
			
				<div id="action_updatereport">
					<% if FieldMap.action_updatereport %>
						$FieldMap.action_updatereport.Field
					<% end_if %>
				</div>
			
				<div style="clear: both">&nbsp;</div>
			<% end_if %>
			
			$FieldMap.ReportContent.FieldHolder
			
			<% loop HiddenFields %>$Field<% end_loop %>
			
			</fieldset>
		</div>
			
		
		<div class="clear"><!-- --></div>
</form>