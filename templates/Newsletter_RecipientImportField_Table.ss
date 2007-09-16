<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<% base_tag %>
	$MetaTags
	<style type="text/css" media="screen">
		   @import "mot/css/css.css";
		   @import "mot/css/typography.css";
		</style>
	<style type="text/css" media="print">
		   @import "mot/css/print.css";
	</style>
	<script src="jsparty/prototype.js" type="text/javascript"></script>
	<script src="jsparty/behaviour.js" type="text/javascript"></script>
	<script src="cms/javascript/Newsletter_UploadForm.js" type="text/javascript"></script>	
</head>
<body onload="">
<h1><% _t('CONTENTSOF','Contents of') %> $FileName</h1>
<form method="post" action="admin/newsletter/?executeForm=UploadForm" name="UploadForm">
	<% control CustomSetFields %>
		$FieldHolder
	<% end_control %>
	<input type="submit" name="action_confirm" value="<% _t('YES','Confirm') %>" />
	<input type="submit" name="action_cancel" value="<% _t('NO','Cancel') %>" />
	<input type="hidden" name="ID" value="$TypeID" />
	<table summary="<% _t('RECIMPORTED','Recipients imported from') %> $FileName">
		<tbody>
			<tr>
				<% control ColumnHeaders %>
					<th>
						$Field
					</th>
				<% end_control %>
			</tr>
			<% control Rows %>
			<tr>
				<% control Cells %>
					<td>$Value</td>
				<% end_control %>
			</tr>
			<% end_control %>
		</tbody>
	</table>
</form>
</body>
</html>