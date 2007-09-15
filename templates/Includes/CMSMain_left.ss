<!-- <div class="title"><div style="background-image : url(cms/images/panels/MySite.png)">My Site</div></div> -->

	<div id="treepanes">
		<h2 id="heading_sitetree" class="selected">
			<img id="sitetree_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" style="display:none;" title="click to open this box" />
			<img id="sitetree_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" title="click to close box" />
			Site Content and Structure
		</h2>
		<div id="sitetree_holder">

			<ul id="TreeActions">
				<li class="action" id="addpage"><button>Create...</button></li>
				<li class="action" id="deletepage"><button>Delete...</button></li>
				<li class="action" id="sortitems"><button>Reorder...</button></li>
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
		<h2 id="heading_tasklist">
			<img id="tasklist_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" title="click to open box" />
			<img id="tasklist_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" style="display:none;" title="click to close box" /> 
			Tasklist
		</h2>
		<div class="listpane" id="tasklist_holder" style="display:none">	
			<div class="unitBody">
			</div>
		</div>
		<h2 id="heading_waitingon">
			<img id="waitingon_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" title="click to open box" />
			<img id="waitingon_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" style="display:none;" title="click to close box" /> 
			Waiting on
		</h2>
		<div class="listpane" id="waitingon_holder" style="display:none">	
			<div class="unitBody">
			</div>
		</div>
		<% end_if %>
		<h2 id="heading_versions">
			<img id="versions_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" title="click to open box" />
			<img id="versions_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" style="display:none;" title="click to close box" /> 
			Page Version History
		</h2>
		<div class="listpane" id="versions_holder" style="display:none">
			<p class="pane_actions" id="versions_actions">
				<input type="checkbox" id="versions_comparemode" /> <label for="versions_comparemode">Compare mode (click 2 below)</label>
				<br />

				<input type="checkbox" id="versions_showall" /> <label for="versions_showall">Show unpublished versions</label>
			</p>
			
			<div class="unitBody">
			</div>
		</div>
		<% if EnterpriseCMS %>
		<h2 id="heading_comments">
			<img id="comments_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" title="click to open box" />
			<img id="comments_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" style="display:none;" title="click to close box" /> 
			Comments
		</h2>
		<div class="listpane" id="comments_holder" style="display:none">	
			<div class="unitBody">
			</div>
		</div>
		<% end_if %>
		<h2 id="heading_reports">
			<img id="reports_toggle_closed" src="cms/images/panels/toggle-closed.gif" alt="+" title="click to open box" />
			<img id="reports_toggle_open" src="cms/images/panels/toggle-open.gif" alt="-" style="display:none;" title="click to close box" /> 
			Site Reports
		</h2>
		<div class="listpane" id="reports_holder" style="display:none">
			<p id="ReportSelector_holder">$ReportSelector</p>
			<div class="unitBody">
			</div>
		</div>
	</div>