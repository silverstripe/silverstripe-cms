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
	<style type="text/css">
		body {
			padding-top: 1em;
		}
	</style>
	<script src="jsparty/prototype.js" type="text/javascript"></script>
	<script src="jsparty/behaviour.js" type="text/javascript"></script>
	<script src="cms/javascript/Newsletter_UploadForm.js" type="text/javascript"></script>
<% if ImportMessage %>
	<script type="text/javascript">
		top.statusMessage('Imported new members','good');
	</script>
<% end_if %>	
</head>
<body onload="">
<% if ImportMessage %>
	<p>
		$ImportMessage
		<p><b>Note:</b> To see the new members on the Recipients tab, you need to <a href="#" onclick="javascript:top.reloadRecipientsList()">reload the Mailing List</a>.</p>
		<ul>
			<li><label>New members imported:</label>$NewMembers</li>
			<li><label>Members updated:</label>$ChangedMembers</li>
			<li><label>Number of details changed:</label>$ChangedFields</li>
			<li><label>Records skipped:</label>$SkippedRecords</li>
			<li><label>Time taken: </label>$Time seconds</li>
		</ul>
	</p>
<% end_if %>
<% if ErrorMessage %>
	<p class="message bad">
		$ErrorMessage
	</p>
<% end_if %>
	$UploadForm
</body>
</html>