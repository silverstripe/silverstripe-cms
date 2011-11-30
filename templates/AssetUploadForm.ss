<form $FormAttributes>

	<% if Message %>
	<p id="{$FormName}_error" class="message $MessageType">$Message</p>
	<% else %>
	<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>
	
	<fieldset>
		
		<div class="asset-upload-panel-view">
			<h2>
				<span>1</span>
				Choose files
			</h2>
			<div>
				$FieldMap.Files
				<!-- TODO Add the drop area markup dynamically -->
			</div>,
		</div>
		
		<!-- Hide until first file is selected, then list files and their upload progress -->
		<!-- Similar markup would need to be produced by JavaScript -->
			<div class-"asset-upload-panel-edit">
				<h2>
					<span>2</span>
					Edit &amp; organize
				</h2>
				<ul class="asset-upload-files">
					<% if Files %>
					<% loop Files %>
						<li data-id="$ID" class="asset-upload-file">
							<% include AssetUploadForm_File %>
						</li>
					<% end_loop %>
					<% end_if %>
				</ul>
			</div>
			
			<% loop HiddenFields %>
				$FieldHolder
			<% end_loop %>
	</fieldset>

	<% if Actions %>
	<div class="Actions">
		<% control Actions %>
			$Field
		<% end_control %>
	</div>
	<% end_if %>

</form>