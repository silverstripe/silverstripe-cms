<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<% base_tag %>
<title>Print</title>
</head>

<%-- <body onload="window.print();"> --%>
<body>
	<% with $Form.Controller %>
		<h1 style="margin-bottom: 0">$CurrentReport.Title</h1>
		<% with $CurrentReport.getCmsFields %>
			<% if $Name == Filters %>
				<h2 style="margin-bottom: 0; margin-top: 0;">Filters</h2>
				<ul style="margin-top: 0">
				<% loop $FieldSet %>
					<li>$Title = $performReadonlyTransformation.Field</li>
				<% end_loop %>
				</ul>
			<% end_if %>
		<% end_with %>
	<% end_with %>
	<% include TableListField %>
</body>
</html>