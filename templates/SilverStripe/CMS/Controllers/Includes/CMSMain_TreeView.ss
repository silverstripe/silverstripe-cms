<% include SilverStripe\\CMS\\Controllers\\CMSMain_ContentToolActions View='Tree' %>

$ExtraTreeTools

<%-- TreeViewEntwine.js mounts the React tree view on this element. --%>
<div class="cms-tree-view flexbox-area-grow"
    data-tree-id="$ModelClass.ATT"
    data-title-row-text="$CMSTreeTitle.ATT"
    data-label="<%t SilverStripe\CMS\Controllers\CMSMain.SITE_PAGE_NAVIGATION 'Site Page Navigation' %>"
></div>
