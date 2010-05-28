<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<% base_tag %>
<title>Print</title>
</head>

<!-- <body onload="window.print();"> -->
<body>
	<% control Form.Controller %>
		<h1 style="margin-bottom: 0">$CurrentReport.Title</h1>
		<% control CurrentReport.getCmsFields %>
			<% if Name == Filters %>
				<h2 style="margin-bottom: 0; margin-top: 0;">Filters</h2>
				<ul style="margin-top: 0">
				<% control FieldSet %>
					<li>$Title = $performReadonlyTransformation.Field</li>
				<% end_control %>
				</ul>
			<% end_if %>
		<% end_control %>
	<% end_control %>
	<% include TableListField %>
</body>
</html>