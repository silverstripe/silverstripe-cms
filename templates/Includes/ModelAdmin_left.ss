<div id="LeftPane">
	<!-- <h2><% _t('SEARCHLISTINGS','Search Listings') %></h2> -->
	<div id="SearchForm_holder" class="leftbottom">		
	    <% if SearchClassSelector = tabs %>
		<ul class="tabstrip">
		<% control ModelForms %>
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
		
		<% control ModelForms %>
		<div class="tab" id="{$Form.Name}_$ClassName">
			<h3>Create</h3>
			$CreateForm
			
			<h3>Search</h3>
			$SearchForm
		
			<h3>Import</h3>
			$ImportForm
			</div>
		<% end_control %>
	</div>
</div>