<div class="cms-content center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">
	<% with $AddForm %>
		<form $FormAttributes data-layout-type="border">
			<div class="toolbar--north container-fluid">
				<div class="toolbar__navigation">
					<ol class="breadcrumb">
						<li class="breadcrumb__item breadcrumb__item--last">
							<h2 class="breadcrumb__item-title breadcrumb__item-title--last">
								<% _t('CMSAddPageController.Title','Add page') %>
							</h2>
						</li>
					</ol>
				</div>
			</div>

			<div class="panel-scrollable panel-scrollable--double-toolbar container-fluid cms-panel-padded">
				<% if $Message %>
				<p id="{$FormName}_error" class="message $MessageType">$Message</p>
				<% else %>
				<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
				<% end_if %>

				<fieldset>
					<% if $Legend %><legend>$Legend</legend><% end_if %>
					<% loop $Fields %>
						$FieldHolder
					<% end_loop %>
				</fieldset>
			</div>

			<div class="toolbar--south">
				<% if $Actions %>
				<div class="btn-toolbar">
					<% loop $Actions %>
						$Field
					<% end_loop %>
				</div>
				<% end_if %>
			</div>
		</form>
	<% end_with %>
</div>
