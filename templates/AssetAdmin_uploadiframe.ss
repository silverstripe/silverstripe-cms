<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<style>
body {
	padding: 0;
	margin: 0;
	background-color: #fff !important;
}
fieldset {
	padding: 0;
	margin: 0;
	border-style: none;
}
</style>
<% base_tag %>
</head>

<body>
<% if CanUpload %>
$UploadForm
<% else %>
<% _t('PERMFAILED','You do not have permission to upload files into this folder.') %>

<% end_if %>
</body>