<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" style="overflow:auto">
	<head>
		<% base_tag %>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><% _t('TITLE', 'Image Uploading Iframe') %></title>
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<style type="text/css">
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
	</head>
	<body>
	<% if CanUpload %>
		$UploadForm
		<% if UploadMetadataHtml %>
			<div id="metadataFormTemplate" style="display:none">
				$UploadMetadataHtml
			</div>
		<% end_if %>
	<% else %>
		<% _t('PERMFAILED','You do not have permission to upload files into this folder.') %>
	<% end_if %>
	<script type="text/javascript">
		var multi_selector = new MultiSelector($('Form_UploadForm_FilesList'), null, $('Form_UploadForm_action_upload'));
		multi_selector.addElement($('Form_UploadForm_Files-0'));
	</script>
	</body>
</html>