<div class="WidgetAreaEditor" id="WidgetAreaEditor" name="$Name">
	<input type="hidden" id="$Name" name="$IdxField" value="$Value" />
	<div class="availableWidgetsHolder">
		<h2><% _t('AVAILABLE', 'Available Widgets') %></h2>
		<p>&nbsp;</p>
		<div class="availableWidgets" id="WidgetAreaEditor_availableWidgets">
			<% if AvailableWidgets %>
				<% control AvailableWidgets %>
					$DescriptionSegment
				<% end_control %>
			<% else %>
				<div id="NoWidgets">
					<p><% _t('NOAVAIL', 'There are currently no widgets available.') %></p>
				</div>
			<% end_if %>
		</div>
	</div>
	<div class="usedWidgetsHolder">
		<h2><% _t('INUSE', 'Widgets currently used') %></h2>
		<p><% _t('TOADD', 'To add widgets, drag them from the left area to here.') %></p>
		
		<div class="usedWidgets" id="WidgetAreaEditor_usedWidgets">
			<% if UsedWidgets %>
				<% control UsedWidgets %>
					$EditableSegment
				<% end_control %>
			<% else %>
				<div id="NoWidgets"></div>
			<% end_if %>
		</div>
	</div>
</div>