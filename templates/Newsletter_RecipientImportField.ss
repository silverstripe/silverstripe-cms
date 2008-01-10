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
			padding-bottom: 2em;
			overflow: scroll !important;
		}
	</style>
	<script src="jsparty/prototype.js" type="text/javascript"></script>
	<script src="jsparty/behaviour.js" type="text/javascript"></script>
	<script src="cms/javascript/Newsletter_UploadForm.js" type="text/javascript"></script>
<% if ImportMessage %>
	<script type="text/javascript">
		top.statusMessage('<% _t('IMPORTNEW','Imported new members') %>','good');
		top.reloadRecipientsList();
	</script>
<% end_if %>	
</head>
<body onload="">
<% if ImportMessage %>
	<p>
		$ImportMessage
		<p><b>Note:</b><% _t('MLRELOAD1', 'To see the new members on the Recipients tab, you need to') %> <a href="#" onclick="javascript:top.reloadRecipientsList()"><% _t('MLRELOAD2', 'reload the Mailing List') %></a>.</p>
		<ul>
			<li><label><% _t('IMPORTED','New members imported:') %></label>$NewMembers</li>
			<li><label><% _t('UPDATED','Members updated:') %></label>$ChangedMembers</li>
			<li><label><% _t('CHANGED','Number of details changed:') %></label>$ChangedFields</li>
			<li><label><% _t('SKIPPED','Records skipped:') %></label>$SkippedRecords</li>
			<li><label><% _t('TIME','Time taken:') %> </label>$Time <% _t('SEC','seconds') %></li>
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