<div class="title"><div>Functions</div></div>

<div id="treepanes">
<div id="sitetree_holder" style="overflow:auto">
	<% if BulkLoaders %>
		<ul id="sitetree" class="tree unformatted">
		<li id="$ID" class="root Root"><a>Batch entry functions</a>
	
			<ul>
			<% control BulkLoaders %>
				<li id="record-$class">
				<a href="admin/bulkload/show/$class">$Title</a>
				</li>
			<% end_control %>
			</ul>
		</li>
		</ul>
	<% end_if %>
</div>
</div>
