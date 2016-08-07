<% require javascript(sapphire/thirdparty/tabstrip/tabstrip.js) %>
<% require css(sapphire/thirdparty/tabstrip/tabstrip.css) %>

<div id="LeftPane">
	<div id="SearchForm_holder" class="leftbottom">		
		<% if SearchClassSelector = tabs %>
			<ul class="tabstrip">
				<% control ModelForms %>
					<li class="$FirstLast"><a id="tab-ModelAdmin_$Title.HTMLATT" href="#{$Form.Name}_$ClassName">$Title</a></li>
				<% end_control %>
			</ul>
		<% end_if %>
		
		<% if SearchClassSelector = dropdown %>
			<p id="ModelClassSelector">
				<% _t('ModelAdmin.SEARCHFOR','Search for:') %>
				<select>
					<% control ModelForms %>
						<option value="{$Form.Name}_$ClassName">$Title</option>
					<% end_control %>
				</select>
			</p>
		<% end_if %>
		
		<% control ModelForms %>
			<div class="tab" id="{$Form.Name}_$ClassName">
				$Content
			</div>
		<% end_control %>
	</div>
</div>
