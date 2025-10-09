<li id="record-{$node.ID}" data-id="{$node.ID}" data-recordtype="{$node.ClassName}" class="$markingClasses $extraClass" role="none"><span class="jstree-icon jstree-icon--arrow"<% if $count %> role="button" aria-label="{$toggleChildPagesLabel.ATT}" aria-expanded="<% if $opened %>true<% else %>false<% end_if %>"<% else %> aria-hidden="true"<% end_if %>><span class="font-icon-right-dir" aria-hidden="true"></span>&nbsp;</span>
    <%-- IMPORTANT: There MUST NOT be any whitespace between the <a> element and the <ins> element below or it will break things in the JS --%>
    <a href="{$Controller.LinkRecordEdit($node.ID).ATT}" title="{$Title.ATT}" role="treeitem" aria-level="{$level}"<% if $count %> aria-expanded="<% if $opened %>true<% else %>false<% end_if %>"<% end_if %><% if $isCurrentPage %> tabindex="0" aria-current="page"<% else_if not $hasCurrentPage && $isFirstPage %> tabindex="0"<% else %> tabindex="-1"<% end_if %>><ins class="jstree-icon jstree-icon--drag-handle"><span class="font-icon-drag-handle" aria-hidden="true"></span>&nbsp;</ins>
        <span class="text">{$TreeTitle}</span>
    </a>
    $SubTree
</li>
