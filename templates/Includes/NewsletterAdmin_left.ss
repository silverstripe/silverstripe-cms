<style>
ul.tree li.MailType span.MailType a {
	background-image: url(cms/images/treeicons/sent-folder.png);
	font-weight: bold;
}
ul.tree li.MailType span.Recipients a {
	background-image: url(cms/images/treeicons/multi-user.png);
}
ul.tree li.MailType span.Recipients a {
	background-image: url(cms/images/treeicons/multi-user.png);
}
ul.tree li.MailType span.SentFolder a, ul.tree span.SentFolder.children a {
	background-image: url(cms/images/treeicons/sent-folder.png);
}
ul.tree li.MailType span.DraftFolder a, ul.tree span.DraftFolder.children a  {
	background-image: url(cms/images/treeicons/draft-folder.png);
}
ul.tree li.MailType span.Draft a {
	background-image: url(cms/images/treeicons/draft-file.png);
}
ul.tree li.MailType span.Sent a {
	background-image: url(cms/images/treeicons/sent-file.gif);
}
</style>

<div class="title"><div>Newsletters</div></div>
<div id="treepanes">
	<div id="sitetree_holder">
		<ul id="TreeActions">
			<li class="action" id="addtype"><button>Create...</button></li>
			<li class="action" id="deletedrafts"><button>Delete...</button></li>
		</ul>
		<div style="clear:both;"></div>
		<form class="actionparams" id="addtype_options" style="display: none" action="admin/newsletter/addtype">
			<input type="hidden" name="ParentID" value="" />
				<select name="PageType" id="add_type">
					<option value="type">Add new type</option>
					<option value="draft">Add new draft</option>
				</select>
			<input class="action" type="submit" value="Go" />
		</form>
		
		<form class="actionparams" id="deletedrafts_options" style="display: none" action="admin/newsletter/remove">
			<p>Select the drafts that you want to delete and then click the button below</p>
			<input type="hidden" name="csvIDs" />
			<input type="submit" value="Delete the selected drafts" />
		</form>
		<% include NewsletterAdmin_SiteTree %>
	</div>
</div>
