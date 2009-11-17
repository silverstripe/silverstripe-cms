<div class="WidgetAreaEditor" id="WidgetAreaEditor-$Name" name="$Name">
	<input type="hidden" id="$Name" name="$IdxField" value="$Value" />
	<div class="availableWidgetsHolder">
		<h2><% _t('AVAILABLE', 'Available Widgets') %></h2>
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
		<h2><% _t('INUSE', 'Widgets currently used') %></h2>
		<p><% _t('TOADD', 'To add widgets, click on the purple header on the left') %></p>
		
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