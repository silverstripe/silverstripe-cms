<div id="$id" class="$CSSClasses $extraClass field dragdrop" href="$CurrentLink">
	<div class="FileFilter filterBox">
		$SearchForm
	</div>
	<% if Markable %>
		<% include TableListField_SelectOptions %>
	<% end_if %>
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<th width="18">&nbsp;</th>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">$Title</th>
				<% end_control %>
				<th width="18">&nbsp;</th>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tbody>
			<% if Items %>
				<% control Items %>
					<% include AssetTableField_Item %>
				<% end_control %>
			<% else %>
				<tr class="notfound">
					<td></td>
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% sprintf(_t('AssetTableField.NODATAFOUND', 'No %s found'),$NamePlural) %></i></td>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	<div class="utility">
		$DeleteMarkedButton
		<% control Utility %>
			<span class="item"><a href="$Link">$Title</a></span>
		<% end_control %>
	</div>
</div>