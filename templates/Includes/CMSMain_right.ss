<div class="ui-layout-center">
<% if EditForm %>
	$EditForm
<% else %>
	<h1>$ApplicationName</h1>
	<p>
		<% _t('WELCOMETO','Welcome to') %> $ApplicationName! 
		<% _t('CHOOSEPAGE','Please choose a page from the left.') %>
	</p>
<% end_if %>
</div>

<div class="ui-layout-south">
	<div id="form_actions_right" class="ajaxActions"></div>
</div>

<!--<p id="statusMessage" style="visibility:hidden"></p>-->
