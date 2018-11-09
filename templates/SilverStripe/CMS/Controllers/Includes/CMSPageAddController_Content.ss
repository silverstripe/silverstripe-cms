<div class="flexbox-area-grow cms-content $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">
	<% with $AddForm %>
		<form $FormAttributes data-layout-type="border">
			<div class="toolbar toolbar--north">
				<div class="toolbar__navigation">
					<ol class="breadcrumb">
						<li class="breadcrumb__item">
                            <%t SilverStripe\CMS\Controllers\CMSPagesController.MENUTITLE 'Pages'%>
                        </li>
						<li class="breadcrumb__item breadcrumb__item--last breadcrumb__item--no-crumb">
				            <h2 class="breadcrumb__item-title breadcrumb__item-title--last">
						        <%t SilverStripe\CMS\Controllers\CMSPageAddController.Title 'Add page' %>
				            </h2>
			            </li>
					</ol>
				</div>
			</div>

			<div class="panel panel--padded panel--scrollable flexbox-area-grow">
				<% if $Message %>
				<p id="{$FormName}_error" class="alert $AlertType">$Message</p>
				<% else %>
				<p id="{$FormName}_error" class="alert $AlertType" style="display: none"></p>
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
