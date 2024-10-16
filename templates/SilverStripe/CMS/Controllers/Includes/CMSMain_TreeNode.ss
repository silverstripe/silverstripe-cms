<li id="record-{$node.ID}" data-id="{$node.ID}" data-recordtype="{$node.ClassName}" class="$markingClasses $extraClass"><ins class="jstree-icon font-icon-right-dir">&nbsp;</ins>
    <a href="{$Controller.LinkRecordEdit($node.ID).ATT}" title="{$Title.ATT}"><ins class="jstree-icon font-icon-drag-handle">&nbsp;</ins>
        <span class="text">{$TreeTitle}</span>
    </a>
    $SubTree
</li>
