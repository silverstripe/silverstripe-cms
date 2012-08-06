<div class="cms-tree-view-modes">
	<span><% _t("TreeTools.DisplayLabel","Display:") %></span>
	<% if CanOrganiseSitetree %> 
	<div class="checkboxAboveTree">
		<input type="radio" name="view-mode" class="view-mode" value="draggable" id="view-mode-draggable" checked="checked" />
		<label for="view-mode-draggable"><% _t("CMSPagesController_ContentToolbar.ss.ENABLEDRAGGING","Drag'n'drop") %></label>
	</div>
	<% end_if %>
	<div>
		<input type="radio" name="view-mode" class="view-mode" value="multiselect" id="view-mode-multiselect" />
		<label for="view-mode-multiselect"><% _t("CMSPagesController_ContentToolbar.ss.MULTISELECT","Multi-selection") %></label>
	</div>
</div>
<% include CMSPagesController_ContentToolActions %>
