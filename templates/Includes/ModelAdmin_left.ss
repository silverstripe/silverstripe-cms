<div id="LeftPane">
	<h2><% _t('SEARCHLISTINGS','Search Listings') %></h2>
	<div id="SearchForm_holder" class="leftbottom">		
	    <% if SearchClassSelector = tabs %>
		<ul class="tabstrip">
		<% control SearchForms %>
			<li class="$FirstLast"><a href="#{$Form.Name}_$ClassName">$Title</a></li>
		<% end_control %>
		</ul>
		<% end_if %>
		
		<% if SearchClassSelector = dropdown %>
		<p id="ModelClassSelector">
		    Search for:
    		<select>
            	<% control SearchForms %>
            		<option value="{$Form.Name}_$ClassName">$Title</option>
            	<% end_control %>
    		</select>
    	</p>
    	<% end_if %>
		
		<% control SearchForms %>
		<div class="tab" id="{$Form.Name}_$ClassName">
		$Form
		</div>
		<% end_control %>
	</div>
	<h2><% _t('ADDLISTING','Add Listing') %></h2>
	<div id="AddForm_holder" class="lefttop">
		<ul class="tabstrip">
			<li class="first"><a href="#Form_ManagedModelsSelect_holder"><% _t('ADD_TAB_HEADER','Add') %></a></li>
			<% if ImportForm %><li class="first"><a href="#Form_ImportForm_holder"><% _t('IMPORT_TAB_HEADER','Import') %></a></li><% end_if %>
		</ul>
		<div class="tab" id="Form_ManagedModelsSelect_holder">
			$ManagedModelsSelect
		</div>
		<% if ImportForm %>
		<div class="tab" id="Form_ImportForm_holder">
			$ImportForm
		</div>
		<% end_if %>
	</div>
	<!--
	<div id="ResultTable_holder" class="leftbottom">
	</div>
	-->
</div>