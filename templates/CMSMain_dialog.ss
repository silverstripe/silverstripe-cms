<html>
<head>
<script>
function buttonClick(el) {
	<% if Modal %>
	window.returnValue = el.name;
	window.close();
	<% else %>
	if(window.linkedObject) {
		if(el.name) {
			window.linkedObject.result = el.name;
		
		} else {
			throw("Couldn't find the button name");
		}
	} else {
		throw("Can't find window.linkedObject to send the button click back to the main window");
	}
	window.close();
	<% end_if %>
}
</script>
</head>

<body>

<p>$Message</p>

<p style="align: center">
<% control Buttons %>
	<input type="button" value="$Title" name="$Name" onclick="buttonClick(this);" />
<% end_control %>
</p>

</body>
</html>