<div class="WidgetAreaEditor" id="WidgetAreaEditor-$Name" name="$Name"<% if MaxWidgets %> maxwidgets="$MaxWidgets"<% end_if %>>
	<input type="hidden" id="$Name" name="$IdxField" value="$Value" />
	<div class="availableWidgetsHolder">
		<h2>$AvailableTitle</h2>
		<p>&nbsp;</p>
		<div class="availableWidgets" id="availableWidgets-$Name">
			<% if AvailableWidgets %>
				<% control AvailableWidgets %>
					$DescriptionSegment
				<% end_control %>
			<% else %>
				<div class="NoWidgets" id="NoWidgets-$Name">
					<p><% _t('NOAVAIL', 'There are currently no widgets available.') %></p>
				</div>
			<% end_if %>
		</div>
	</div>
	<div class="usedWidgetsHolder">
		<h2>$InUseTitle</h2>
		<p>$ToAddTitle</p>
		
		<div class="usedWidgets" id="usedWidgets-$Name">
			<% if UsedWidgets %>
				<% control UsedWidgets %>
					$EditableSegment
				<% end_control %>
			<% else %>
				<div class="NoWidgets" id="NoWidgets-$Name"></div>
			<% end_if %>
		</div>
	</div>
</div>