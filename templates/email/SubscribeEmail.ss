<html>
	<head>
		<style type="text/css">
				div.data span {
					width: 50%;
				}
			
				div.data span.left {
					text-align: right;
					font-weight: bold;
				}
				
				div.data a {
					overflow: visible;
				}
		</style>
	</head>
	<body>
		<h1>$Subject</h1>
		<p>Dear $FirstName,</p>
		<p>Thanks for signing up to our mailing list. The following data was submitted:</p>

		<p>First name: $FirstName</p>
		<p>Email: $Email</p>
		<p>Password: $Password (we've generated this password for you)</p>
		
		<% if Newsletters %>
			<p>You're subscribed to the following mailing lists:</p>
			<ul>
				<% control Newsletters %>
					<li>$Title</li>
				<% end_control %>
			</ul>
		<% end_if %>
	</body>
</html>
