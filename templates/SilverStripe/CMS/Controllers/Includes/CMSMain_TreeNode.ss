<li id="record-{$node.ID}" data-id="{$node.ID}" data-pagetype="{$node.ClassName}" class="$markingClasses $extraClass"><ins class="jstree-icon">&nbsp;</ins>
    <a href="{$node.CMSEditLink.ATT}" title="{$Title.ATT}"><ins class="jstree-icon">&nbsp;</ins>
        <span class="text">{$node.TreeTitle}</span>
    </a>
    $SubTree
</li>
