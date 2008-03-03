<h2><% _t('STATISTICS', 'Statistics') %></h2>

<div id="treepanes">
	<div id="sitetree_holder">
		<ul id="sitetree" class="tree unformatted">
			<li class="Root" id="statsroot"><a><% _t('REPTYPES','Report Types') %></a>
				<li id="stoverview"><a href="$baseURL/admin/statistics/overview"><% _t('OVERV','Overview') %></a></li>
				<li id="stusers"><a href="$baseURL/admin/statistics/users"><% _t('USERS','Users') %></a></li>
				<li id="stviews"><a href="$baseURL/admin/statistics/views"><% _t('VIEWS', 'Views') %></a></li>
				<li id="sttrends"><a href="$baseURL/admin/statistics/trends"><% _t('TRENDS', 'Trends') %></a></li>
				<li id="stos"><a href="$baseURL/admin/statistics/os"><% _t('OS', 'Operating Systems') %></a></li>
				<li id="stbrowsers"><a href="$baseURL/admin/statistics/browsers"><% _t('BROWSERS', 'Browsers') %></a></li>
			</li>
		</ul>
	</div>
</div>
