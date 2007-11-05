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

<h2><% _t('NEWSLETTERS','Newsletters') %></h2>
<div id="treepanes" style="overflow-y: auto;">
		<ul id="TreeActions">
			<li class="action" id="addtype"><button><% _t('CREATE','Create') %></button></li>
			<li class="action" id="deletedrafts"><button><% _t('DEL','Delete') %></button></li>
		</ul>
		<div style="clear:both;"></div>
		<form class="actionparams" id="addtype_options" style="display: none" action="admin/newsletter/add">
			<input type="hidden" name="ParentID" value="" />
				<select name="PageType" id="add_type">
					<option value="type"><% _t('ADDTYPE','Add new type') %></option>
					<option value="draft"><% _t('ADDDRAFT','Add new draft') %></option>
				</select>
			<input class="action" type="submit" value="<% _t('GO','Go') %>" />
		</form>
		
		<form class="actionparams" id="deletedrafts_options" style="display: none" action="admin/newsletter/remove">
			<p><% _t('SELECTDRAFTS','Select the drafts that you want to delete and then click the button below') %></p>
			<input type="hidden" name="csvIDs" />
			<input type="submit" value="<% _t('DELETEDRAFTS','Delete the selected drafts') %>" />
		</form>
		<% include NewsletterAdmin_SiteTree %>
</div>
