<div id="MemberListField">
	<div class="MemberListFilter">
		<a class="showhide closed" href="#">Filter</a>
		<div id="MemberListFilterControls">
			<input id="MemberListBaseGroupID" type="hidden" name="MemberListBaseGroup" value="$GroupID" />
			<input id="MemberListDontShowPassword" type="hidden" name="MemberListDontShowPassword" value="$DontShowPassword" />
			$SearchField
			$OrderByField
			$GroupFilter
			<input id="MemberListFilterButton" type="submit" value="Filter" name="filter" />
		</div>
	</div>
	<div id="MemberList">
	<% include MemberList_Table %>
	</div>
</div>

