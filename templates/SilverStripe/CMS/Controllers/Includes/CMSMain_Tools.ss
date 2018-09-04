<div class="cms-content-tools fill-height cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSMain">
    <div class="cms-content-header north vertical-align-items">
        <div class="cms-content-header-info vertical-align-items fill-width">
            <div class="section-heading flexbox-area-grow">
                <span class="section-label"><a href="$LinkPages">{$MenuCurrentItem.Title}</a></span>
            </div>
            <% include SilverStripe\\CMS\\Controllers\\CMSMain_Filter %>
        </div>
    </div>
    <div class="panel panel--scrollable flexbox-area-grow fill-height cms-panel-content">
        <div class="cms-content-filters cms-content-filters--hidden">
            <div
                class="search-holder search-holder--cms"
                data-schema="$SearchFieldSchema"
            ></div>
        </div>
        $PageListSidebar
    </div>
    <div class="cms-panel-content-collapsed">
        <h3 class="cms-panel-header">$SiteConfig.Title</h3>
    </div>
    <div class="toolbar toolbar--south cms-panel-toggle">
        <a class="toggle-expand" href="#"><span>&raquo;</span></a>
        <a class="toggle-collapse" href="#"><span>&laquo;</span></a>
    </div>
</div>
