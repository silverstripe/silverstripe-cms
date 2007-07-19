<div class="title"><div style="background-image : url(cms/images/panels/MySite.png)">My Site</div></div>

	<ul style="float: left; clear: left;" id="SideTabs">
		<li id="sidetab_sitetree" class="selected">Site Map</li>
		<!--<li id="sidetab_search">Search</li>-->
		<% if EnterpriseCMS %>
		<li id="sidetab_tasklist">Task List</li>
		<li id="sidetab_waitingon">Waiting on</li>
		<% end_if %>
		<li id="sidetab_versions">Versions</li>
		<% if EnterpriseCMS %>
		<li id="sidetab_comments">Comments</li>
		<% end_if %>
		<li id="sidetab_reports">Reports</li>
	</ul>
	
	<div id="treepanes">
	
		<div id="sitetree_holder">

			<ul id="TreeActions">
				<li class="action" id="addpage"><a href="admin/addpage">Create</a></li>
				<li class="action" id="deletepage"><a href="admin/deletepage">Delete</a></li>
				<li class="action" id="sortitems"><a href="#">Reorganise</a></li>
				<!-- <li class="action" id="duplicate"><a href="#">Duplicate</a></li>
				Sam: this should be put into the Create area, I think, so we don't stuff up the layout -->
			</ul>
			<div style="clear:both;"></div>
			<% control AddPageOptionsForm %>
			<form class="actionparams" id="$FormName" style="display: none" action="$FormAction">
				<% control Fields %>
				$FieldHolder
				<% end_control %>
				<!--
				<div>
				<select name="Type">
					<% control PageTypes %>
					<option value="$ClassName">$AddAction</option>
					<% end_control %>
				</select>
				<input type="hidden" name="ParentID" />
				</div>
				-->
				<div>
				<input class="action" type="submit" value="Go" />
				</div>
				
			</form>
			<% end_control %>
		
			<form class="actionparams" id="deletepage_options" style="display: none" action="admin/deleteitems">
				<p>Select the pages that you want to delete and then click the button below</p>
				<div>		
				<input type="hidden" name="csvIDs" />
				<input type="submit" value="Delete the selected pages" />
				</div>
			</form>
		
			<form class="actionparams" id="sortitems_options" style="display: none">
				<p id="sortitems_message" style="margin: 0">To reorganise your site, drag the pages around as desired.</p>
			</form>
			
			<% control DuplicatePagesOptionsForm %>
			<form class="actionparams" id="duplicate_options" style="display: none" action="admin/duplicateSiteTree">
				<p>Select the pages that you want to duplicate, whether it's children should be included, and where you want the duplicates placed</p>
				<div>		
					<input type="hidden" name="csvIDs" />
					<input type="submit" value="Duplicate" />
				</div>
			</form>
			<% end_control %>

			<div id="publication_key" style="border-bottom: 1px #CCC solid; background-color: #EEE; padding: 3px;">
				Key:
				<ins style="cursor: help" title="Added to the stage site and not published yet">new</ins>
				<del style="cursor: help" title="Deleted from the stage site but still on the live site">deleted</del>
				<span style="cursor: help" title="Edited on the stage site and not published yet" class="modified">changed</span>
			</div>


		$SiteTreeAsUL
		</div>
		<!--<div id="search_holder" style="display:none">
			<h2>Search</h2>
			<div class="unitBody">
			</div>
		</div>-->

		<% if EnterpriseCMS %>
		<div class="listpane" id="tasklist_holder" style="display:none">
			<h2>Tasklist</h2>
			<div class="unitBody">
			</div>
		</div>
		<div class="listpane" id="waitingon_holder" style="display:none">
			<h2>Waiting on</h2>
			<div class="unitBody">
			</div>
		</div>
		<% end_if %>
		<div class="listpane" id="versions_holder" style="display:none">
			<h2>History</h2>
			
			<p class="pane_actions" id="versions_actions">
				<select>
					<option value="view" selected="selected">View (click to see)</option>
					<option value="compare" >Compare (click 2 to see)</option>
				</select>
				<br />

				<input type="checkbox" id="versions_showall" /> Show unpublished versions
			</p>
			
			<div class="unitBody">
			</div>
		</div>
		<% if EnterpriseCMS %>
		<div class="listpane" id="comments_holder" style="display:none">
			<h2>Comments</h2>
			<div class="unitBody">
			</div>
		</div>
		<% end_if %>
		<div class="listpane" id="reports_holder" style="display:none">
			<h2>Reports</h2>
			<p id="ReportSelector_holder">$ReportSelector</p>
			<div class="unitBody">
			</div>
		</div>
	</div>