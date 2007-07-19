<% if NewsletterTypes %>
	<ul id="sitetree" class="tree unformatted">
	<% control NewsletterTypes %>
		<li id="mailtype_$ID" class="MailType">
			<a href="#">$Title</a>
			<ul>
				<li id="drafts_$ID" class="DraftFolder nodelete"><a href="$baseURL/admin/showtype/$ID">Drafts</a>
				<% if DraftNewsletters %>
					<ul>
						<% control DraftNewsletters %>
						<li class="Draft" id="draft_{$ParentID}_{$ID}"><a href="$baseURL/admin/newsletter/shownewsletter/$ID">$Title</a></li>
						<% end_control %>
					</ul>
				<% end_if %>
				</li>
				<li id="sent_$ID" class="SentFolder nodelete"><a href="$baseURL/admin/showtype/$ID">Sent Items</a>
                <% if SentNewsletters %>
                    <ul>
                        <% control SentNewsletters %>
                        <li class="Sent" id="sent_{$ParentID}_{$ID}"><a href="$baseURL/admin/newsletter/shownewsletter/$ID">$Title</a></li>
                        <% end_control %>
                    </ul>
                <% end_if %>
                </li>
                <li id="recipients_$ID" class="Recipients nodelete"><a href="$baseURL/admin/newsletter/showtype/$ID">Mailing List</a></li>
            </ul>
		</li>
	<% end_control %>
	</ul>
<% end_if %>
