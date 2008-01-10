<div class="$ClassName Widget" id="$Name">
	<h3 class="handle">$CMSTitle</h3>
	<div class="widgetDescription">
		<p>$Description</p>
	</div>
	<div class="widgetFields">
		$CMSEditor
		<input type="hidden" name="$Name[Type]" value="$ClassName" />   
		<input type="hidden" name="$Name[Sort]" value="$Sort" />
	</div>
	<p class="deleteWidget"><span class="widgetDelete"><% _t('DELETE', 'Delete') %></span></p>
</div>