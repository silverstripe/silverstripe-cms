<style>
#left_separator	{
	border-bottom: 3px solid #d4d0c8;
}

#SearchFrom_holder{
	border-bottom: 1px solid #808080;
}

</style>

<div id="LeftPane">
	<h2><% _t('ADDLISTING','Add Listing') %></h2>
	<div id="SearchForm_holder" class="lefttop" style="overflow:auto">
		$AddForm
	</div>
	<h2><% _t('SEARCHLISTINGS','Search Listings') %></h2>
	<div id="SearchForm_holder" class="leftbottom" style="overflow:auto">
		$SearchForm
	</div>
	<div id="left_separator">
	&nbsp;
	</div>
	<h2><% _t('SEARCHRESULTS','Search Results') %></h2>
	<div id="ResultTable_holder" class="leftbottom">
		$Results
	</div>
</div>