<div class="cms-content center $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<h2><% _t('REPORTS','Reports') %></h2>
	</div>


	<div class="cms-content-tools west">
		
		<ul class="ui-widget-content">
		<% control Reports %>
			<li id="record-$ID">
				<a href="admin/reports/show/$ID" title="$TreeDescription">$TreeTitle</a>
			</li>
		<% end_control %>
		</ul>
		
	</div>

	<div class="cms-content-fields center ui-widget-content">
		$EditForm
	</div>
	
</div>