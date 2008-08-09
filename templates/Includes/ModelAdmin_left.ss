<div id="LeftPane">
	<h2><% _t('ADDLISTING','Add Listing') %></h2>
	<div id="AddForm_holder" class="lefttop">
		<ul class="tabstrip">
			<li class="first"><a href="#Form_ManagedModelsSelect_holder"><% _t('ADD_TAB_HEADER','Add') %></a></li>
			<li class="first"><a href="#Form_ImportForm_holder"><% _t('IMPORT_TAB_HEADER','Import') %></a></li>
		</ul>
		<div class="tab" id="Form_ManagedModelsSelect_holder">
			$ManagedModelsSelect
		</div>
		<div class="tab" id="Form_ImportForm_holder">
			$ImportForm
		</div>
	</div>
	<h2><% _t('SEARCHLISTINGS','Search Listings') %></h2>
	<div id="SearchForm_holder" class="leftbottom">		
		<ul class="tabstrip">
		<% control SearchForms %>
			<li class="first"><a href="#{$Form.Name}_$ClassName">$Title</a></li>
		<% end_control %>
		</ul>
		<% control SearchForms %>
			<div class="tab" id="{$Form.Name}_$ClassName">
			$Form
			</div>
		<% end_control %>
	</div>
	<h2><% _t('SEARCHRESULTS','Search Results') %></h2>
	<div id="ResultTable_holder" class="leftbottom">
	</div>
</div>