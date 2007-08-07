<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

  <head>
		<% base_tag %>
		$MetaTags
		<link rel="stylesheet" type="text/css" href="$project/css/layout.css" />	
		<link rel="stylesheet" type="text/css" href="$project/css/typography.css" />
		
		<!--[if IE 6]>
			<style type="text/css">
			 @import url($project/css/ie6.css);
			</style> 
		<![endif]-->
		
		<!--[if IE 7]>
			<style type="text/css">
			 @import url($project/css/ie7.css);
			</style> 
		<![endif]-->
	</head>
<body>
<div id="BgContainer">
	<div id="Container">
		<div id="Header">
	   		<h1>Your Site Name</h1>
	    </div>
	    
	    <div id="NavHolder">
			<% if Menu(1) %>
				<% include Menu1 %>
			<% end_if %>
		</div>
	  	
	  	<div class="clear"><!-- --></div>
		
		<div id="Layout">
		  $Layout
		</div>
		
	   <div class="clear"><!-- --></div>
	</div>
	<% include Footer %> 
</div>

</body>
</html>