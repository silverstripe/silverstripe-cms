<div class="WidgetAreaEditor" id="WidgetAreaEditor" name="$Name">
	<input type="hidden" id="$Name" name="$IdxField" value="$Value" />
	<div class="availableWidgetsHolder">
		<h2>Available Widgets</h2>
		<div class="availableWidgets" id="WidgetAreaEditor_availableWidgets">
			<% control AvailableWidgets %>
				$DescriptionSegment
			<% end_control %>
		</div>
	</div>
	<div class="usedWidgetsHolder">
		<h2>Widgets currently used</h2>
		<div class="usedWidgets" id="WidgetAreaEditor_usedWidgets">
			<% if UsedWidgets %>
				<% control UsedWidgets %>
					$EditableSegment
				<% end_control %>
			<% else %>
				<div id="NoWidgets">
					<p>To add widgets, drag them from the left area to here.</p>
				</div>
			<% end_if %>
		</div>
	</div>
</div>