<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<% base_tag %>
<title>$ApplicationName</title>

<link rel="stylesheet" type="text/css" href="cms/css/typography.css" />
<link rel="stylesheet" type="text/css" href="cms/css/layout.css" />
<link rel="stylesheet" type="text/css" href="cms/css/cms_left.css" />
<link rel="stylesheet" type="text/css" href="cms/css/cms_right.css" />
</head>

<body class="stillLoading">
	<div id="Loading" style="background: #FFF url(cms/images/loading.gif) 50% 50% no-repeat; position: absolute;z-index: 100000;height: 100%;width: 100%;margin: 0;padding: 0;z-index: 100000;position: absolute;">Loading...</div>

	<div id="top">
		<% include CMSTopMenu %>	
	</div><div id="left" style="float:left">
		$Left
	
	</div>
	<div id="separator" style="float:left">
		&nbsp;
	</div>
	<div class="right" id="right">
		$Right
	</div>

	<% if RightBottom %>
	<div class="right" id="rightbottom">
		$RightBottom
	</div>
	<% end_if %>
	
	<div id="bottom">
		<div class="holder">
		<div id="logInStatus">
			<a href="http://www.silverstripe.com" title="Silverstripe Website">Silverstripe CMS</a>&nbsp;-&nbsp;
			<abbr style="border-style: none" title="This is the $ApplicationName version that you are currently running, technically it's the CVS branch">$CMSVersion</abbr> &nbsp; &nbsp; &nbsp; 
			<% control CurrentMember %>
			Logged in as $FirstName $Surname - <a href="Security/logout">log out</a>
		<% end_control %>
		</div>

		<div id="switchView" class="bottomTabs">
			<% if class = CMSMain %>
			<div class="blank"> View page in: </div>
			<% else %>
			<div class="blank"> Switch to: </div>
			<% end_if %>
			<a class="current">CMS</a>
			<a id="viewStageSite" href="home/?stage=Stage" style="left : -1px;">Draft Site</a>
			<a id="viewLiveSite" href="home/?stage=Live" style="left : -3px;">Published Site</a>
			<a style="display: none;left : -5px;" id="viewArchivedSite" href="home/">Archived Site</a>
		</div>
		
		</div>
	</div>
	<script type="text/javascript">Behaviour.addLoader(hideLoading)</script>
</body>
</html>