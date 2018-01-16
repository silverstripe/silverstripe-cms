<% if $BackLinkTracking %>
    <table class="table">
        <thead>
        <tr>
            <th><%t SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_INDEX '#' %></th>
            <th><%t SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_USED_ON 'Used on' %></th>
            <th><%t SilverStripe\CMS\Model\SiteTreeFileExtension.TITLE_TYPE 'Type' %></th>
        </tr>
        </thead>
        <tbody>
        <% loop $BackLinkTracking %>
            <tr>
                <td>$Pos</td>
                <td><a href="$CMSEditLink">$MenuTitle</a></td>
                <td>
                    $i18n_singular_name
                    <% if $isPublished %>
                        <span class="badge badge-success">Published</span>
                    <% else %>
                        <span class="badge status-addedtodraft">Draft</span>
                    <% end_if %>
                </td>
            </tr>
        <% end_loop %>
        </tbody>
    </table>
<% end_if %>
