<li id="record-{$node.ID}" data-id="{$node.ID}" data-recordtype="{$node.ClassName}" class="$markingClasses $extraClass">
    <ins class="jstree-icon jstree-icon--arrow" tabindex="0"><span class="font-icon-right-dir" aria-hidden="true"></span>&nbsp;</ins>
    <%-- IMPORTANT: There MUST NOT be any whitespace between the <a> element and the <ins> element below or it will break things in the JS --%>
    <a href="{$Controller.LinkRecordEdit($node.ID).ATT}" title="{$Title.ATT}"><ins class="jstree-icon jstree-icon--drag-handle"><span class="font-icon-drag-handle" aria-hidden="true"></span>&nbsp;</ins>
        <span class="text">{$TreeTitle}</span>
    </a>
    $SubTree
</li>
