<div class="PageControls">
	<input name="MemberListStart" id="MemberListStart" type="hidden" value="$MemberListStart" />
	<% if LastLink %><a class="Last" href="$LastLink" title="<% _t('VIEWLAST', 'View last') %> $PageSize <% _t('LASTMEMBERS', 'members') %>"><img src="cms/images/pagination/record-last.png" alt="View last $PageSize members" /></a>
	<% else %><span class="Last"><img src="cms/images/pagination/record-last-g.png" alt="<% _t('VIEWLAST', 'View last') %> $PageSize <% _t('LASTMEMBERS', 'members') %>" /></span><% end_if %>
	<% if FirstLink %><a class="First" href="$FirstLink" title="<% _t('VIEWFIRST', 'View first') %> $PageSize <% _t('FIRSTMEMBERS', 'members') %>"><img src="cms/images/pagination/record-first.png" alt="View first $PageSize members" /></a>
	<% else %><span class="First"><img  src="cms/images/pagination/record-first-g.png" alt="<% _t('VIEWFIRST', 'View first') %> $PageSize <% _t('FIRSTMEMBERS', 'members') %>" /></span><% end_if %>
	<% if PrevLink %><a class="Prev" href="$PrevLink" title="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize <% _t('PREVIOUSMEMBERS', 'members') %>"><img src="cms/images/pagination/record-prev.png" alt="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize <% _t('PREVIOUSMEMBERS', 'members') %>" /></a>
	<% else %><img class="Prev" src="cms/images/pagination/record-prev-g.png" alt="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize <% _t('PREVIOUSMEMBERS', 'members') %>" /><% end_if %>
	<span class="Count">
		<% _t('DISPLAYING', 'Displaying') %> $FirstMember <% _t('TO', 'to') %> $LastMember <% _t('OF', 'of') %> $TotalMembers
	</span>
	<% if NextLink %><a class="Next" href="$NextLink" title="<% _t('VIEWNEXT', 'View next') %> $PageSize <% _t('NEXTMEMBERS', 'members') %>"><img src="cms/images/pagination/record-next.png" alt="<% _t('VIEWNEXT', 'View next') %> $PageSize <% _t('NEXTMEMBERS', 'members') %>" /></a>
	<% else %><img class="Next" src="cms/images/pagination/record-next-g.png" alt="<% _t('VIEWNEXT', 'View next') %> $PageSize <% _t('NEXTMEMBERS', 'members') %>" /><% end_if %>
	
</div>
