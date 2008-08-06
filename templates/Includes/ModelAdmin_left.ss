<div id="LeftPane">
	<h2><% _t('ADDLISTING','Add Listing') %></h2>
	<div id="AddForm_holder" class="lefttop">
		$AddForm
	</div>
	<h2><% _t('SEARCHLISTINGS','Search Listings') %></h2>
	<div id="SearchForm_holder" class="leftbottom">		
		<ul class="tabstrip">
		<% control SearchForms %>
			<li class="first"><a href="#$Form.Name">$Title</a></li>
		<% end_control %>
		</ul>
		<% control SearchForms %>
			<div class="tab" id="$Form.Name">
			$Form
			</div>
		<% end_control %>
	</div>
	<h2><% _t('SEARCHRESULTS','Search Results') %></h2>
	<div id="ResultTable_holder" class="leftbottom">
	</div>
</div>